<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case JazzCash = 'jazzcash';
    case Easypaisa = 'easypaisa';
    case Cod = 'cod';
}
