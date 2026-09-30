<?php
declare(strict_types=1);
return static function (PDO $db): void {
    $stmt=$db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='trip_reports' AND index_name='uq_trip_reports_trip'");
    $stmt->execute();
    if((int)$stmt->fetchColumn()>0){$db->exec('ALTER TABLE trip_reports DROP INDEX uq_trip_reports_trip');}
    // Link historical signups to matching grotto user accounts so participants can access their trips and reports.
    $db->exec("UPDATE trip_participants tp INNER JOIN trips t ON t.id=tp.trip_id INNER JOIN users u ON u.grotto_id=t.grotto_id AND LOWER(u.email)=LOWER(tp.email) SET tp.user_id=u.id WHERE tp.user_id IS NULL");
};
