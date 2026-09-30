<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class WaiverService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @param array<string, mixed> $trip @param array<int, array<string, mixed>> $participants */
    public function finalize(array $trip, array $participants, int $finalizedByUserId): int
    {
        if (empty($trip['waiver_template_id'])) {
            throw new \InvalidArgumentException('This trip does not have a waiver template selected.');
        }
        $existing = $this->latestForTrip((int)$trip['id']);
        if ($existing) {
            return (int)$existing['id'];
        }

        $activeParticipants = array_values(array_filter(
            $participants,
            static fn(array $p): bool => in_array((string)$p['participant_status'], ['registered', 'signed'], true)
        ));
        if ($activeParticipants === []) {
            throw new \InvalidArgumentException('At least one active participant is required before finalizing a waiver.');
        }
        foreach ($activeParticipants as $participant) {
            if (empty($participant['signed_at']) || empty($participant['signature_data'])) {
                throw new \InvalidArgumentException('All active participants must sign before the waiver can be finalized.');
            }
        }

        $stmt = $this->db->prepare('SELECT * FROM waiver_templates WHERE id=:id AND grotto_id=:grotto_id AND active=1 LIMIT 1');
        $stmt->execute(['id'=>(int)$trip['waiver_template_id'], 'grotto_id'=>(int)$trip['grotto_id']]);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \InvalidArgumentException('Selected waiver template was not found or is inactive.');
        }

        $html = $this->renderFinalHtml($trip, $template, $activeParticipants);
        $pdf = (new WaiverPdfService())->render($html);
        $snapshot = json_encode($this->snapshotParticipants($activeParticipants), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $token = TokenService::make();

        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT id FROM trips WHERE id=:id FOR UPDATE');
            $lock->execute(['id'=>(int)$trip['id']]);
            $existing = $this->latestForTrip((int)$trip['id']);
            if ($existing) {
                $this->db->commit();
                return (int)$existing['id'];
            }
            $stmt = $this->db->prepare('INSERT INTO generated_waivers
                (trip_id,waiver_template_id,public_token,final_html,participant_snapshot_json,pdf_data,pdf_sha256,finalized_by_user_id,finalized_at,created_at)
                VALUES (:trip_id,:waiver_template_id,:public_token,:final_html,:participant_snapshot_json,:pdf_data,:pdf_sha256,:finalized_by_user_id,NOW(),NOW())');
            $stmt->bindValue(':trip_id', (int)$trip['id'], PDO::PARAM_INT);
            $stmt->bindValue(':waiver_template_id', (int)$trip['waiver_template_id'], PDO::PARAM_INT);
            $stmt->bindValue(':public_token', $token);
            $stmt->bindValue(':final_html', $html);
            $stmt->bindValue(':participant_snapshot_json', $snapshot);
            $stmt->bindValue(':pdf_data', $pdf, PDO::PARAM_LOB);
            $stmt->bindValue(':pdf_sha256', hash('sha256', $pdf));
            $stmt->bindValue(':finalized_by_user_id', $finalizedByUserId, PDO::PARAM_INT);
            $stmt->execute();
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function unfinalizeForTesting(int $tripId): int
    {
        $stmt = $this->db->prepare('DELETE FROM generated_waivers WHERE trip_id = :trip_id');
        $stmt->execute(['trip_id' => $tripId]);
        return $stmt->rowCount();
    }

    /** @param array<string,mixed> $trip @return array{name:string,html:string}|null */
    public function renderForSignup(array $trip): ?array
    {
        $templateId = (int)($trip['waiver_template_id'] ?? 0);
        if ($templateId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM waiver_templates WHERE id = :id AND grotto_id = :grotto_id AND active = 1 LIMIT 1');
        $stmt->execute(['id' => $templateId, 'grotto_id' => (int)$trip['grotto_id']]);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \InvalidArgumentException('The waiver selected for this trip is unavailable. Please contact the trip leader.');
        }

        $replacements = [
            '{{GROTTO_NAME}}' => $this->e((string)($trip['grotto_name'] ?? '')),
            '{{LANDOWNER_NAME}}' => $this->e((string)($trip['landowner_name'] ?? '')),
            '{{CAVE_NAME}}' => $this->e((string)($trip['cave_name'] ?? '')),
            '{{CAVE_DESCRIPTION}}' => $this->caveDescription($trip),
            '{{TRIP_TITLE}}' => $this->e((string)($trip['title'] ?? '')),
            '{{TRIP_DATE}}' => $this->e((string)($trip['trip_date'] ?? '')),
            '{{FINALIZED_DATE}}' => '',
            '{{PARTICIPANT_LIST}}' => '',
            '{{PARTICIPANT_SIGNATURE_BLOCKS}}' => '',
            '{{SIGNATURE_BLOCKS}}' => '',
        ];

        return [
            'name' => (string)$template['name'],
            'html' => strtr((string)$template['html_body'], $replacements),
        ];
    }

    /** @return array<string, mixed>|null */
    public function latestForTrip(int $tripId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM generated_waivers WHERE trip_id = :trip_id ORDER BY finalized_at DESC, id DESC LIMIT 1');
        $stmt->execute(['trip_id' => $tripId]);
        $waiver = $stmt->fetch();
        return $waiver ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT gw.*, t.title AS trip_title, t.trip_number, g.name AS grotto_name
            FROM generated_waivers gw
            INNER JOIN trips t ON t.id = gw.trip_id
            INNER JOIN grottos g ON g.id = t.grotto_id
            WHERE gw.public_token = :token
            LIMIT 1');
        $stmt->execute(['token' => trim($token)]);
        $waiver = $stmt->fetch();
        return $waiver ?: null;
    }

    /** @param array<string, mixed> $trip @param array<string, mixed> $template @param array<int, array<string, mixed>> $participants */
    private function renderFinalHtml(array $trip, array $template, array $participants): string
    {
        $participantList = '<ol class="participant-list">';
        $signatureBlocks = '<div class="signature-blocks">';
        foreach ($participants as $participant) {
            $name = $this->e((string)$participant['name']);
            $email = $this->e((string)$participant['email']);
            $signedAt = $this->e((string)$participant['signed_at']);
            $participantList .= '<li><strong>' . $name . '</strong>' . ($email !== '' ? ' &lt;' . $email . '&gt;' : '') . '</li>';
            $signatureBlocks .= '<div class="signature-block">';
            $signatureBlocks .= '<div><strong>Printed Name:</strong> ' . $name . '</div>';
            if ((int)($participant['is_minor'] ?? 0) === 1) {
                $signatureBlocks .= '<div class="minor-label">Minor Participant</div>';
                $signatureBlocks .= '<div><strong>Parent/Guardian:</strong> ' . $this->e((string)($participant['guardian_name'] ?? '')) . '</div>';
            }
            $signatureBlocks .= '<img class="signature-image" alt="Signature of ' . $name . '" src="' . $this->e((string)$participant['signature_data']) . '">';
            $signatureBlocks .= '<div><strong>Date Signed:</strong> ' . $signedAt . '</div>';
            $signatureBlocks .= '</div>';
        }
        $participantList .= '</ol>';
        $signatureBlocks .= '</div>';

        $templateBody = (string)$template['html_body'];
        $hasListPlaceholder = str_contains($templateBody, '{{PARTICIPANT_LIST}}');
        $hasSignaturePlaceholder = str_contains($templateBody, '{{PARTICIPANT_SIGNATURE_BLOCKS}}') || str_contains($templateBody, '{{SIGNATURE_BLOCKS}}');
        $replacements = [
            '{{GROTTO_NAME}}' => $this->e((string)($trip['grotto_name'] ?? '')),
            '{{LANDOWNER_NAME}}' => $this->e((string)($trip['landowner_name'] ?? '')),
            '{{CAVE_NAME}}' => $this->e((string)($trip['cave_name'] ?? '')),
            '{{CAVE_DESCRIPTION}}' => $this->caveDescription($trip),
            '{{TRIP_TITLE}}' => $this->e((string)$trip['title']),
            '{{TRIP_DATE}}' => $this->e((string)$trip['trip_date']),
            '{{FINALIZED_DATE}}' => date('F j, Y'),
            '{{PARTICIPANT_LIST}}' => $participantList,
            '{{PARTICIPANT_SIGNATURE_BLOCKS}}' => $signatureBlocks,
            '{{SIGNATURE_BLOCKS}}' => $signatureBlocks,
        ];
        $body = strtr($templateBody, $replacements);
        if (!$hasListPlaceholder || !$hasSignaturePlaceholder) {
            $body .= '<section class="waiver-participants"><h2>Participants and Signatures</h2>';
            if (!$hasListPlaceholder) { $body .= $participantList; }
            if (!$hasSignaturePlaceholder) { $body .= $signatureBlocks; }
            $body .= '</section>';
        }
        $body .= '<p class="finalization-note">Finalized ' . $this->e(date('F j, Y g:i A')) . '. This document is a snapshot of the waiver text, roster, and signatures at finalization.</p>';
        return '<article class="final-waiver"><h1>' . $this->e((string)$template['name']) . '</h1>' . $body . '</article>';
    }

    /** @param array<int,array<string,mixed>> $participants */
    private function snapshotParticipants(array $participants): array
    {
        return array_map(static fn(array $p): array => [
            'participant_id' => (int)($p['id'] ?? 0),
            'name' => (string)($p['name'] ?? ''),
            'email' => (string)($p['email'] ?? ''),
            'is_minor' => (int)($p['is_minor'] ?? 0),
            'guardian_name' => (string)($p['guardian_name'] ?? ''),
            'signed_at' => (string)($p['signed_at'] ?? ''),
            'signature_data' => (string)($p['signature_data'] ?? ''),
        ], $participants);
    }

    /** @param array<string,mixed> $trip */
    private function caveDescription(array $trip): string
    {
        $parts = [];
        $name = trim((string)($trip['cave_name'] ?? ''));
        $county = trim((string)($trip['cave_county'] ?? ''));
        $state = trim((string)($trip['cave_state'] ?? ''));
        if ($name !== '') { $parts[] = $name; }
        $area = trim(implode(', ', array_filter([$county !== '' ? $county . ' County' : '', $state])));
        if ($area !== '') { $parts[] = $area; }
        return $this->e(implode(' — ', $parts));
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
