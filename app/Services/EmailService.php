<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Application;

final class EmailService
{
    public function __construct(private readonly Application $app) {}

    public function send(int $grottoId, string $to, string $subject, string $textBody): void
    {
        $settingsService = new EmailSettingsService($this->app);
        $settings = $settingsService->findForGrotto($grottoId);
        if ($settings === null) throw new \RuntimeException('Email has not been configured for this grotto.');
        (new SmtpMailer())->send($settings, $settingsService->password($settings), $to, $subject, $textBody);
    }
}
