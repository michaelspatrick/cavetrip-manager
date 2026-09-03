<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\CaveService;
use CaveTrip\Services\LandownerService;

final class CaveController extends BaseController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireMember($app); $grottoId = $this->grottoId($currentUser);
        return View::render($app, 'caves/index', ['title'=>'Caves','currentUser'=>$currentUser,'caves'=>(new CaveService($app->db()))->listForGrotto($grottoId, true)]);
    }
    public function create(Application $app): string
    {
        $currentUser = $this->requireMember($app); $grottoId = $this->grottoId($currentUser);
        return View::render($app, 'caves/form', ['title'=>'Add Cave','currentUser'=>$currentUser,'cave'=>null,'landowners'=>(new LandownerService($app->db()))->listForGrotto($grottoId),'action'=>'/caves']);
    }
    public function store(Application $app): string
    {
        Http::requirePostCsrf(); $currentUser=$this->requireMember($app); $grottoId=$this->grottoId($currentUser);
        try { $id=(new CaveService($app->db()))->create($grottoId, $_POST); $this->audit($app)->caveCreated($grottoId,$this->userId($currentUser),$id); Session::flash('success','Cave created.'); return Http::redirect('/caves'); }
        catch (\Throwable $e) { Session::flash('error','Unable to create cave: '.$e->getMessage()); return Http::redirect('/caves/create'); }
    }
    public function edit(Application $app): string
    {
        $currentUser=$this->requireMember($app); $grottoId=$this->grottoId($currentUser); $id=(int)($_GET['id']??0); $cave=(new CaveService($app->db()))->findForGrotto($id,$grottoId);
        if ($cave===null) { http_response_code(404); return View::render($app,'pages/404',['title'=>'Cave Not Found']); }
        return View::render($app,'caves/form',['title'=>'Edit Cave','currentUser'=>$currentUser,'cave'=>$cave,'landowners'=>(new LandownerService($app->db()))->listForGrotto($grottoId),'action'=>'/caves/update?id='.$id]);
    }
    public function update(Application $app): string
    {
        Http::requirePostCsrf(); $currentUser=$this->requireMember($app); $grottoId=$this->grottoId($currentUser); $id=(int)($_GET['id']??0);
        try { (new CaveService($app->db()))->update($id,$grottoId,$_POST); $this->audit($app)->caveUpdated($grottoId,$this->userId($currentUser),$id); Session::flash('success','Cave updated.'); return Http::redirect('/caves'); }
        catch (\Throwable $e) { Session::flash('error','Unable to update cave: '.$e->getMessage()); return Http::redirect('/caves/edit?id='.$id); }
    }
}
