<?php
declare(strict_types=1);
namespace CaveTrip\Services;
use CaveTrip\Core\Application;
final class NotificationService
{
    public function __construct(private readonly Application $app) {}
    public function configured(int $grottoId):bool { return (new EmailSettingsService($this->app))->findForGrotto($grottoId)!==null; }
    public function send(int $grottoId,string $to,string $subject,string $body):bool { if(!$this->configured($grottoId)||!filter_var($to,FILTER_VALIDATE_EMAIL))return false; try{(new EmailService($this->app))->send($grottoId,$to,$subject,$body);return true;}catch(\Throwable){return false;} }
    public function signup(array $trip,string $to):bool { $body="You are registered for {$trip['title']} on {$trip['trip_date']}.\n\nTrip: ".app_url('/trips/show?id='.(int)$trip['id'])."\n";return $this->send((int)$trip['grotto_id'],$to,'Trip registration: '.$trip['title'],$body); }
    public function cancellation(array $trip,string $to,string $reason):bool { $body="The trip {$trip['title']} scheduled for {$trip['trip_date']} has been cancelled.\n".($reason!==''?"\nReason: {$reason}\n":'');return $this->send((int)$trip['grotto_id'],$to,'Trip cancelled: '.$trip['title'],$body); }
    public function finalWaiver(array $trip,string $to,string $token):bool { $body="The final waiver for {$trip['title']} is available here:\n\n".app_url('/waivers/view?token='.urlencode($token))."\n";return $this->send((int)$trip['grotto_id'],$to,'Final waiver: '.$trip['title'],$body); }
}
