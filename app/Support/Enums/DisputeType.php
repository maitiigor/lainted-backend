<?php

namespace App\Support\Enums;

enum DisputeType: string
{
    case NotReceived = 'not_received';
    case GradeMismatch = 'grade_mismatch';
    case WrongItem = 'wrong_item';
    case ItemDamaged = 'item_damaged';
    case SellerNoShow = 'seller_no_show';
    case Other = 'other';
}
