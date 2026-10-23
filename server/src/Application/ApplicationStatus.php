<?php

declare(strict_types=1);

namespace App\Application;

enum ApplicationStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InReview => 'На рассмотрении',
            self::Approved => 'Одобрена',
            self::Rejected => 'Отклонена',
        };
    }

    /** Название вкладки фильтра в админке */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::New => 'Новые',
            self::InReview => 'На рассмотрении',
            self::Approved => 'Одобренные',
            self::Rejected => 'Отклонённые',
        };
    }
}
