<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\CaveService;
use CaveTrip\Services\LandownerService;
use CaveTrip\Services\TripCalloutContactService;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\TripService;
use CaveTrip\Services\WaiverService;
use CaveTrip\Services\WaiverTemplateService;

final class TripController extends BaseController
{
    public function index(Application $app): string
    {
        $currentUser=$this->requireLogin($app); $grottoId=$this->grottoId($currentUser);
        $trips=(new TripService($app->db()))->listForUser($grottoId,$currentUser);
        return View::render($app,'trips/index',['title'=>(string)$currentUser['role']==='guest'?'My Trips':'Trips','currentUser'=>$currentUser,'trips'=>$trips]);
    }
    public function create(Application $app):string{$u=$this->requireMember($app);return $this->form($app,$u,null,'/trips','Create Trip');}
    public function store(Application $app):string
    {
        Http::requirePostCsrf();$u=$this->requireMember($app);$g=$this->grottoId($u);
        try{$id=(new TripService($app->db()))->create($g,$_POST,$u);(new TripCalloutContactService($app->db()))->replaceForTrip($id,$_POST);$this->audit($app)->tripCreated($g,$this->userId($u),$id);Session::flash('success','Trip created.');return Http::redirect('/trips/show?id='.$id);}catch(\Throwable $e){Session::flash('error','Unable to create trip: '.$e->getMessage());return Http::redirect('/trips/create');}
    }
    public function show(Application $app):string
    {
        $u=$this->requireLogin($app);$id=(int)($_GET['id']??0);$trip=(new TripService($app->db()))->findForUser($id,$this->grottoId($u),$u);
        if($trip===null){http_response_code(404);return View::render($app,'pages/404',['title'=>'Trip Not Found']);}
        $ps=new TripParticipantService($app->db());$ps->ensureSignatureTokensForTrip((int)$trip['id']);
        return View::render($app,'trips/show',['title'=>'Manage Trip','currentUser'=>$u,'trip'=>$trip,'participants'=>$ps->listForTrip((int)$trip['id']),'latestWaiver'=>(new WaiverService($app->db()))->latestForTrip((int)$trip['id']),'calloutContacts'=>(new TripCalloutContactService($app->db()))->listForTrip((int)$trip['id'])]);
    }
    public function edit(Application $app):string
    {
        $u=$this->requireMember($app);$g=$this->grottoId($u);$id=(int)($_GET['id']??0);$trip=(new TripService($app->db()))->findForGrotto($id,$g);
        if($trip===null||!$this->canManageTrip($u,$trip)){http_response_code(404);return View::render($app,'pages/404',['title'=>'Trip Not Found']);}
        return $this->form($app,$u,$trip,'/trips/update?id='.$id,'Edit Trip');
    }
    public function update(Application $app):string
    {
        Http::requirePostCsrf();$u=$this->requireMember($app);$g=$this->grottoId($u);$id=(int)($_GET['id']??0);$s=new TripService($app->db());$trip=$s->findForGrotto($id,$g);
        if($trip===null||!$this->canManageTrip($u,$trip)){http_response_code(404);return View::render($app,'pages/404',['title'=>'Trip Not Found']);}
        try{$s->update($id,$g,$_POST,$u);(new TripCalloutContactService($app->db()))->replaceForTrip($id,$_POST);$this->audit($app)->tripUpdated($g,$this->userId($u),$id);Session::flash('success','Trip updated.');return Http::redirect('/trips/show?id='.$id);}catch(\Throwable $e){Session::flash('error','Unable to update trip: '.$e->getMessage());return Http::redirect('/trips/edit?id='.$id);}
    }
    public function cancel(Application $app):string
    {
        Http::requirePostCsrf();$u=$this->requireMember($app);$g=$this->grottoId($u);$id=(int)($_GET['id']??0);$s=new TripService($app->db());$trip=$s->findForGrotto($id,$g);
        if($trip===null||!$this->canManageTrip($u,$trip)){http_response_code(404);return View::render($app,'pages/404',['title'=>'Trip Not Found']);}
        $reason=(string)($_POST['cancellation_reason']??'');try{$s->cancel($id,$g,$this->userId($u),$reason);$this->audit($app)->tripCancelled($g,$this->userId($u),$id,$reason);Session::flash('success','Trip cancelled.');}catch(\Throwable $e){Session::flash('error','Unable to cancel trip: '.$e->getMessage());}return Http::redirect('/trips/show?id='.$id);
    }
    /** @param array<string,mixed> $u @param array<string,mixed> $trip */
    private function canManageTrip(array $u,array $trip):bool{return in_array((string)$u['role'],['super_admin','admin'],true)||(int)$trip['trip_leader_user_id']===(int)$u['id'];}
    /** @param array<string,mixed> $u @param array<string,mixed>|null $trip */
    private function form(Application $app,array $u,?array $trip,string $action,string $title):string
    {
        $g=$this->grottoId($u);$contacts=$trip?(new TripCalloutContactService($app->db()))->listForTrip((int)$trip['id']):[];
        return View::render($app,'trips/form',['title'=>$title,'currentUser'=>$u,'trip'=>$trip,'action'=>$action,'caves'=>(new CaveService($app->db()))->listForGrotto($g),'landowners'=>(new LandownerService($app->db()))->listForGrotto($g),'waiverTemplates'=>(new WaiverTemplateService($app->db()))->listActiveForGrotto($g),'calloutContacts'=>$contacts]);
    }
}
