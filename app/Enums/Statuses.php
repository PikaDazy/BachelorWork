<?php

namespace App\Enums;

enum Statuses: string
{
    case placed = 'o-map-pin';
    case paid = 'o-credit-card';
    case produced = 'o-cog-6-tooth';
    case shipped = 'o-truck';
    case delivered = 'o-check';
}
