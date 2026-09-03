<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class TripService
{
    public function __construct(private readonly PDO $db) {}

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(int $grottoId): array
    {
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE trips.grotto_id = :grotto_id
            ORDER BY trips.trip_date DESC, trips.meeting_time DESC, trips.id DESC');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $currentUser @return array<int,array<string,mixed>> */
    public function listForUser(int $grottoId, array $currentUser): array
    {
        $role = (string)($currentUser['role'] ?? 'guest');
        $userId = (int)($currentUser['id'] ?? 0);
        if (in_array($role, ['super_admin', 'admin'], true)) {
            return $this->listForGrotto($grottoId);
        }
        if ($role === 'guest') {
            return $this->listForParticipantUser($userId);
        }

        $stmt = $this->db->prepare($this->baseSelect() . "
            WHERE trips.grotto_id = :grotto_id
              AND (
                    trips.visibility = 'core_group'
                    OR trips.trip_leader_user_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM trip_participants visible_tp
                        WHERE visible_tp.trip_id = trips.id
                          AND visible_tp.user_id = :user_id2
                          AND visible_tp.participant_status NOT IN ('removed','cancelled')
                    )
              )
            ORDER BY trips.trip_date DESC, trips.meeting_time DESC, trips.id DESC");
        $stmt->execute(['grotto_id'=>$grottoId,'user_id'=>$userId,'user_id2'=>$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForParticipantUser(int $userId): array
    {
        $stmt = $this->db->prepare($this->baseSelect() . "
            INNER JOIN trip_participants auth_tp ON auth_tp.trip_id = trips.id
            WHERE auth_tp.user_id = :user_id AND auth_tp.participant_status NOT IN ('removed','cancelled')
            ORDER BY trips.trip_date DESC, trips.id DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findForUser(int $id, int $grottoId, array $currentUser): ?array
    {
        $role=(string)($currentUser['role']??'guest');
        $userId=(int)($currentUser['id']??0);
        if (in_array($role,['super_admin','admin'],true)) return $this->findForGrotto($id,$grottoId);
        if ($role==='guest') return $this->findForParticipantUser($id,$userId);

        $stmt=$this->db->prepare($this->baseSelect()."\n            WHERE trips.id=:id AND trips.grotto_id=:grotto_id
              AND (
                    trips.visibility='core_group'
                    OR trips.trip_leader_user_id=:user_id
                    OR EXISTS (
                        SELECT 1 FROM trip_participants visible_tp
                        WHERE visible_tp.trip_id=trips.id
                          AND visible_tp.user_id=:user_id2
                          AND visible_tp.participant_status NOT IN ('removed','cancelled')
                    )
              ) LIMIT 1");
        $stmt->execute(['id'=>$id,'grotto_id'=>$grottoId,'user_id'=>$userId,'user_id2'=>$userId]);
        $trip=$stmt->fetch(); return $trip?:null;
    }

    /** @return array<string, mixed>|null */
    public function findForParticipantUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . "
            INNER JOIN trip_participants auth_tp ON auth_tp.trip_id = trips.id
            WHERE trips.id = :id AND auth_tp.user_id = :user_id AND auth_tp.participant_status NOT IN ('removed','cancelled')
            LIMIT 1");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $trip = $stmt->fetch(); return $trip ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findForGrotto(int $id, int $grottoId): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . '
            WHERE trips.id = :id AND trips.grotto_id = :grotto_id LIMIT 1');
        $stmt->execute(['id'=>$id,'grotto_id'=>$grottoId]);
        $trip=$stmt->fetch(); return $trip?:null;
    }

    /** @return array<string, mixed>|null */
    public function findByShareToken(string $token): ?array
    {
        $stmt=$this->db->prepare($this->baseSelect().' WHERE trips.share_token=:share_token LIMIT 1');
        $stmt->execute(['share_token'=>trim($token)]); $trip=$stmt->fetch(); return $trip?:null;
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $currentUser */
    public function create(int $grottoId,array $data,array $currentUser):int
    {
        $tripNumber=$this->nextTripNumber($grottoId); $params=$this->bindData($grottoId,$data,$currentUser,$tripNumber);
        $stmt=$this->db->prepare('INSERT INTO trips
            (grotto_id, trip_number, title, trip_date, meeting_time, meeting_location, cave_id, landowner_id,
             waiver_template_id, trip_leader_user_id, min_attendees, max_attendees,
             signup_opens_at, signup_closes_at, visibility, waitlist_enabled, share_token,
             callout_time, status, notes, created_at)
            VALUES
            (:grotto_id,:trip_number,:title,:trip_date,:meeting_time,:meeting_location,:cave_id,:landowner_id,
             :waiver_template_id,:trip_leader_user_id,:min_attendees,:max_attendees,
             :signup_opens_at,:signup_closes_at,:visibility,:waitlist_enabled,:share_token,
             :callout_time,:status,:notes,NOW())');
        $stmt->execute($params); return (int)$this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $currentUser */
    public function update(int $id,int $grottoId,array $data,array $currentUser):void
    {
        $existing=$this->findForGrotto($id,$grottoId); if($existing===null) throw new \InvalidArgumentException('Trip not found.');
        $params=$this->bindData($grottoId,$data,$currentUser,(string)$existing['trip_number']); $params['id']=$id;
        $stmt=$this->db->prepare('UPDATE trips SET title=:title, trip_date=:trip_date, meeting_time=:meeting_time,
            meeting_location=:meeting_location, cave_id=:cave_id, landowner_id=:landowner_id,
            waiver_template_id=:waiver_template_id, min_attendees=:min_attendees, max_attendees=:max_attendees,
            signup_opens_at=:signup_opens_at, signup_closes_at=:signup_closes_at, visibility=:visibility,
            waitlist_enabled=:waitlist_enabled, callout_time=:callout_time, status=:status, notes=:notes,
            updated_at=NOW() WHERE id=:id AND grotto_id=:grotto_id');
        unset($params['trip_number'],$params['share_token'],$params['trip_leader_user_id']); $stmt->execute($params);
    }

    public function cancel(int $id,int $grottoId,int $cancelledByUserId,string $reason):void
    {
        $stmt=$this->db->prepare("UPDATE trips SET status='cancelled', callout_status='cancelled', cancelled_at=NOW(),
            cancelled_by_user_id=:cancelled_by_user_id, cancellation_reason=:cancellation_reason, updated_at=NOW()
            WHERE id=:id AND grotto_id=:grotto_id");
        $stmt->execute(['id'=>$id,'grotto_id'=>$grottoId,'cancelled_by_user_id'=>$cancelledByUserId,'cancellation_reason'=>trim($reason)]);
    }

    private function baseSelect():string
    {
        return "SELECT trips.*, grottos.name AS grotto_name, caves.name AS cave_name, caves.state AS cave_state,
                caves.county AS cave_county, landowners.name AS landowner_name, users.name AS leader_name,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id=trips.id AND tp.participant_status IN ('registered','signed')) AS registered_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id=trips.id AND tp.participant_status='waitlisted') AS waitlist_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id=trips.id AND tp.signed_at IS NOT NULL AND tp.participant_status IN ('registered','signed')) AS signed_count
            FROM trips
            INNER JOIN grottos ON grottos.id=trips.grotto_id
            LEFT JOIN caves ON caves.id=trips.cave_id
            LEFT JOIN landowners ON landowners.id=trips.landowner_id
            LEFT JOIN users ON users.id=trips.trip_leader_user_id";
    }

    private function nextTripNumber(int $grottoId):string
    {
        $stmt=$this->db->prepare('SELECT slug FROM grottos WHERE id=:id LIMIT 1'); $stmt->execute(['id'=>$grottoId]);
        $slug=(string)($stmt->fetchColumn()?:'ctm');
        $stmt=$this->db->prepare('SELECT COUNT(*) FROM trips WHERE grotto_id=:grotto_id AND YEAR(created_at)=YEAR(CURDATE())');
        $stmt->execute(['grotto_id'=>$grottoId]); return TripNumberService::generate($slug,((int)$stmt->fetchColumn())+1);
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $currentUser @return array<string,mixed> */
    private function bindData(int $grottoId,array $data,array $currentUser,string $tripNumber):array
    {
        $min=$this->nullableInt($data['min_attendees']??null); $max=$this->nullableInt($data['max_attendees']??null);
        if($min!==null&&$max!==null&&$min>$max) throw new \InvalidArgumentException('Minimum attendees cannot be greater than maximum attendees.');
        $visibility=(string)($data['visibility']??'core_group'); if(!in_array($visibility,['core_group','selected_members','invite_link','private'],true)) $visibility='core_group';
        $status=(string)($data['status']??'draft'); if(!in_array($status,['draft','open','waiver_signing','finalized','active','completed','cancelled'],true)) $status='draft';
        return ['grotto_id'=>$grottoId,'trip_number'=>$tripNumber,'title'=>$this->requiredString($data['title']??'','Trip title is required.'),
            'trip_date'=>$this->requiredDate($data['trip_date']??'','Trip date is required.'),'meeting_time'=>$this->nullableTime($data['meeting_time']??null),
            'meeting_location'=>$this->nullableString($data['meeting_location']??null),'cave_id'=>$this->nullableInt($data['cave_id']??null),
            'landowner_id'=>$this->nullableInt($data['landowner_id']??null),'waiver_template_id'=>$this->nullableInt($data['waiver_template_id']??null),
            'trip_leader_user_id'=>(int)$currentUser['id'],'min_attendees'=>$min,'max_attendees'=>$max,
            'signup_opens_at'=>$this->nullableDateTime($data['signup_opens_at']??null),'signup_closes_at'=>$this->nullableDateTime($data['signup_closes_at']??null),
            'visibility'=>$visibility,'waitlist_enabled'=>isset($data['waitlist_enabled'])?1:0,'share_token'=>TokenService::make(),
            'callout_time'=>$this->requiredDateTime($data['callout_time']??null),'status'=>$status,'notes'=>$this->nullableString($data['notes']??null)];
    }
    private function requiredString(mixed $v,string $m):string{$v=trim((string)$v);if($v==='')throw new \InvalidArgumentException($m);return $v;}
    private function requiredDate(mixed $v,string $m):string{$v=trim((string)$v);if($v==='')throw new \InvalidArgumentException($m);$d=\DateTime::createFromFormat('Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)throw new \InvalidArgumentException('Trip date must be YYYY-MM-DD.');return $v;}
    private function nullableString(mixed $v):?string{$v=trim((string)$v);return $v===''?null:$v;}
    private function nullableInt(mixed $v):?int{if($v===null||trim((string)$v)==='')return null;return(int)$v;}
    private function nullableTime(mixed $v):?string{$v=trim((string)$v);if($v==='')return null;return preg_match('/^\d{2}:\d{2}$/',$v)?$v:null;}
    private function requiredDateTime(mixed $v):string{$v=$this->nullableDateTime($v);if($v===null)throw new \InvalidArgumentException('Callout date/time is required.');return $v;}
    private function nullableDateTime(mixed $v):?string{$v=trim((string)$v);if($v==='')return null;$v=str_replace('T',' ',$v);if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',$v))return$v.':00';if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$v))return$v;throw new \InvalidArgumentException('Date/time fields must be valid.');}
}
