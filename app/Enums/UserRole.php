<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case WarehouseStaff = 'warehouse';
    case PurchasingStaff = 'purchasing';
    case DeliveryStaff = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
            self::WarehouseStaff => 'Warehouse Staff',
            self::PurchasingStaff => 'Purchasing Staff',
            self::DeliveryStaff => 'Delivery Staff',
        };
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function default(): self
    {
        return self::Administrator;
    }
}
