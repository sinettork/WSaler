<?php

namespace App\Enums;

enum TargetMetric: string
{
    case SalesAmount = 'sales_amount';
    case InvoiceCount = 'invoice_count';
    case CustomerCount = 'customer_count';
    case Quantity = 'quantity';
    case GrossProfit = 'gross_profit';
    case CollectionAmount = 'collection_amount';
    case NewCustomerCount = 'new_customer_count';

    public function label(): string
    {
        return match ($this) {
            self::SalesAmount => 'Sales Amount',
            self::InvoiceCount => 'Number of Invoices',
            self::CustomerCount => 'Number of Customers',
            self::Quantity => 'Quantity Sold',
            self::GrossProfit => 'Gross Profit',
            self::CollectionAmount => 'Collection Amount',
            self::NewCustomerCount => 'New Customer Acquisition',
        };
    }
}