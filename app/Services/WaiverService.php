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

        $activeParticipants = array_values(array_filter($participants, static fn (array $p): bool => in_array($p['participant_status'], ['registered', 'signed'], true)));
        if ($activeParticipants === []) {
            throw new \InvalidArgumentException('At least one active participant is required before finalizing a waiver.');
        }

        foreach ($activeParticipants as $participant) {
            if (empty($participant['signed_at']) || empty($participant['signature_data'])) {
                throw new \InvalidArgumentException('All active participants must sign before the waiver can be finalized.');
            }
        }

        $stmt = $this->db->prepare('SELECT * FROM waiver_templates WHERE id = :id AND grotto_id = :grotto_id AND active = 1 LIMIT 1');
        $stmt->execute(['id' => (int)$trip['waiver_template_id'], 'grotto_id' => (int)$trip['grotto_id']]);
        $template = $stmt->fetch();
        if (!$template) {
            throw new \InvalidArgumentException('Selected waiver template was not found or is inactive.');
        }

        $html = $this->renderFinalHtml($trip, $template, $activeParticipants);
        $token = TokenService::make();

        $stmt = $this->db->prepare('INSERT INTO generated_waivers
            (trip_id, waiver_template_id, public_token, final_html, finalized_by_user_id, finalized_at, created_at)
            VALUES
            (:trip_id, :waiver_template_id, :public_token, :final_html, :finalized_by_user_id, NOW(), NOW())');
        $stmt->execute([
            'trip_id' => (int)$trip['id'],
            'waiver_template_id' => (int)$trip['waiver_template_id'],
            'public_token' => $token,
            'final_html' => $html,
            'finalized_by_user_id' => $finalizedByUserId,
        ]);

        $waiverId = (int)$this->db->lastInsertId();
        $update = $this->db->prepare('UPDATE trips SET status = \'finalized\', updated_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int)$trip['id']]);

        return $waiverId;
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
            $participantList .= "<li><strong>{$name}</strong> &lt;{$email}&gt;</li>";
            $signatureBlocks .= '<div class="signature-block">';
            $signatureBlocks .= '<div><strong>Printed Name:</strong> ' . $name . '</div>';
            if ((int)$participant['is_minor'] === 1) {
                $signatureBlocks .= '<div><strong>Minor Participant</strong></div>';
                $signatureBlocks .= '<div><strong>Parent/Guardian:</strong> ' . $this->e((string)($participant['guardian_name'] ?? '')) . '</div>';
            }
            $signatureBlocks .= '<img class="signature-image" alt="Signature" src="' . $this->e((string)$participant['signature_data']) . '">';
            $signatureBlocks .= '<div><strong>Date Signed:</strong> ' . $signedAt . '</div>';
            $signatureBlocks .= '</div>';
        }
        $participantList .= '</ol>';
        $signatureBlocks .= '</div>';

        $replacements = [
            '{{GROTTO_NAME}}' => $this->e((string)($trip['grotto_name'] ?? '')),
            '{{LANDOWNER_NAME}}' => $this->e((string)($trip['landowner_name'] ?? '')),
            '{{CAVE_NAME}}' => $this->e((string)($trip['cave_name'] ?? '')),
            '{{CAVE_DESCRIPTION}}' => nl2br($this->e((string)($trip['cave_description'] ?? ''))),
            '{{TRIP_TITLE}}' => $this->e((string)$trip['title']),
            '{{TRIP_DATE}}' => $this->e((string)$trip['trip_date']),
            '{{FINALIZED_DATE}}' => date('F j, Y'),
            '{{PARTICIPANT_LIST}}' => $participantList,
            '{{PARTICIPANT_SIGNATURE_BLOCKS}}' => $signatureBlocks,
            '{{SIGNATURE_BLOCKS}}' => $signatureBlocks,
        ];

        $body = strtr((string)$template['html_body'], $replacements);
        return '<article class="final-waiver"><h1>' . $this->e((string)$template['name']) . '</h1>' . $body . '</article>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
