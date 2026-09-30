<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class TripReportService
{
    public function __construct(private readonly PDO $db) {}

    /** @return array<int,array<string,mixed>> */
    public function search(int $grottoId, array $filters=[]): array
    {
        $sql="SELECT r.*, t.title AS trip_title, t.trip_date, c.name AS cave_name, u.name AS author_name
              FROM trip_reports r
              INNER JOIN trips t ON t.id=r.trip_id
              INNER JOIN caves c ON c.id=r.cave_id
              INNER JOIN users u ON u.id=r.author_user_id
              WHERE r.grotto_id=:grotto_id";
        $params=['grotto_id'=>$grottoId];
        $q=trim((string)($filters['q']??''));
        if($q!==''){
            $sql.=" AND (r.summary LIKE :q OR r.conditions LIKE :q OR r.access_observations LIKE :q OR r.hazards LIKE :q OR r.incidents LIKE :q OR r.conservation_observations LIKE :q OR r.follow_up LIKE :q OR r.participant_names_json LIKE :q OR t.title LIKE :q OR c.name LIKE :q OR r.trip_leader_name LIKE :q)";
            $params['q']='%'.$q.'%';
        }
        $caveId=(int)($filters['cave_id']??0);
        if($caveId>0){$sql.=' AND r.cave_id=:cave_id';$params['cave_id']=$caveId;}
        $from=trim((string)($filters['from']??'')); if($from!==''){$sql.=' AND t.trip_date>=:from_date';$params['from_date']=$from;}
        $to=trim((string)($filters['to']??'')); if($to!==''){$sql.=' AND t.trip_date<=:to_date';$params['to_date']=$to;}
        $sql.=' ORDER BY t.trip_date DESC,r.id DESC';
        $stmt=$this->db->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    /** @return array<string,mixed>|null */
    public function findForGrotto(int $id,int $grottoId):?array
    {
        $stmt=$this->db->prepare("SELECT r.*,t.title AS trip_title,t.trip_date,t.trip_leader_user_id,c.name AS cave_name,u.name AS author_name FROM trip_reports r INNER JOIN trips t ON t.id=r.trip_id INNER JOIN caves c ON c.id=r.cave_id INNER JOIN users u ON u.id=r.author_user_id WHERE r.id=:id AND r.grotto_id=:grotto_id LIMIT 1");
        $stmt->execute(['id'=>$id,'grotto_id'=>$grottoId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return $row?:null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listByTrip(int $tripId,int $grottoId):array
    {
        $stmt=$this->db->prepare('SELECT r.*,u.name AS author_name FROM trip_reports r INNER JOIN users u ON u.id=r.author_user_id WHERE r.trip_id=:trip_id AND r.grotto_id=:grotto_id ORDER BY r.submitted_at DESC,r.id DESC');
        $stmt->execute(['trip_id'=>$tripId,'grotto_id'=>$grottoId]); return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public function hasForTrip(int $tripId,int $grottoId):bool
    {
        $stmt=$this->db->prepare('SELECT 1 FROM trip_reports WHERE trip_id=:trip_id AND grotto_id=:grotto_id LIMIT 1');$stmt->execute(['trip_id'=>$tripId,'grotto_id'=>$grottoId]);return(bool)$stmt->fetchColumn();
    }

    public function create(int $grottoId,array $trip,int $authorUserId,array $participantNames,array $data):int
    {
        if((int)($trip['cave_id']??0)<=0) throw new \InvalidArgumentException('A cave must be assigned to the trip before a report can be submitted.');
        $summary=trim((string)($data['summary']??'')); if($summary==='') throw new \InvalidArgumentException('Trip report narrative is required.');
        $stmt=$this->db->prepare('INSERT INTO trip_reports (grotto_id,trip_id,cave_id,author_user_id,trip_leader_name,participant_names_json,summary,conditions,access_observations,hazards,incidents,conservation_observations,follow_up,submitted_at) VALUES (:grotto_id,:trip_id,:cave_id,:author_user_id,:trip_leader_name,:participant_names_json,:summary,:conditions,:access_observations,:hazards,:incidents,:conservation_observations,:follow_up,NOW())');
        $stmt->execute($this->params($grottoId,$trip,$authorUserId,$participantNames,$data)); return (int)$this->db->lastInsertId();
    }

    public function update(int $id,int $grottoId,array $data):void
    {
        $summary=trim((string)($data['summary']??'')); if($summary==='') throw new \InvalidArgumentException('Trip report narrative is required.');
        $stmt=$this->db->prepare('UPDATE trip_reports SET summary=:summary,conditions=:conditions,access_observations=:access_observations,hazards=:hazards,incidents=:incidents,conservation_observations=:conservation_observations,follow_up=:follow_up,updated_at=NOW() WHERE id=:id AND grotto_id=:grotto_id');
        $stmt->execute(['id'=>$id,'grotto_id'=>$grottoId]+$this->textParams($data));
    }

    /** @return array<int,array<string,mixed>> */
    public function completedTripsMissingReport(int $grottoId,int $leaderUserId,bool $admin=false):array
    {
        $sql="SELECT t.id,t.title,t.trip_date,c.name AS cave_name FROM trips t LEFT JOIN caves c ON c.id=t.cave_id LEFT JOIN trip_reports r ON r.trip_id=t.id WHERE t.grotto_id=:grotto_id AND t.status='completed' AND r.id IS NULL";
        $params=['grotto_id'=>$grottoId]; if(!$admin){$sql.=' AND t.trip_leader_user_id=:leader';$params['leader']=$leaderUserId;} $sql.=' ORDER BY t.trip_date DESC';
        $stmt=$this->db->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    private function params(int $grottoId,array $trip,int $authorUserId,array $names,array $data):array
    {
        return ['grotto_id'=>$grottoId,'trip_id'=>(int)$trip['id'],'cave_id'=>(int)$trip['cave_id'],'author_user_id'=>$authorUserId,'trip_leader_name'=>trim((string)($trip['leader_name']??''))?:null,'participant_names_json'=>json_encode(array_values($names),JSON_THROW_ON_ERROR)]+$this->textParams($data);
    }
    private function textParams(array $data):array
    {
        $out=[]; foreach(['summary','conditions','access_observations','hazards','incidents','conservation_observations','follow_up'] as $key){$v=trim((string)($data[$key]??''));$out[$key]=$key==='summary'?$v:($v===''?null:$v);} return $out;
    }
}
