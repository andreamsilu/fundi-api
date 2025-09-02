<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessModelConfig;

class BusinessModelConfigSeeder extends Seeder
{
    public function run(): void
    {
        // C2C (Consumer to Consumer) Configuration
        BusinessModelConfig::updateOrCreate(
            ['business_model' => 'c2c'],
            [
                'allowed_client_roles' => ['customer'],
                'allowed_provider_roles' => ['fundi'],
                'allowed_client_types' => ['individual'],
                'allowed_provider_types' => ['individual'],
                'supported_job_types' => [
                    'homeRepair',
                    'personalService',
                    'eventService',
                    'consultation'
                ],
                'supported_payment_methods' => [
                    'cash',
                    'bank_transfer',
                    'mobile_money',
                    'credit_card'
                ],
                'supported_payment_schedules' => [
                    'immediate',
                    'completion'
                ],
                'minimum_transaction_amount' => 10.00,
                'maximum_transaction_amount' => 10000.00,
                'requires_contract' => false,
                'requires_invoice' => false,
                'requires_insurance' => false,
                'requires_license' => false,
                'requires_background_check' => true,
                'platform_fee_percentage' => 5.00,
                'platform_fee_fixed' => 0.00,
                'minimum_fee' => 0.50,
                'maximum_fee' => 500.00,
                'enabled_features' => [
                    'review_system',
                    'payment_processing',
                    'dispute_resolution'
                ],
                'description' => 'Individual consumers hiring individual service providers for personal and home services.',
                'client_description' => 'Find skilled individuals for home repairs, personal services, events, and consultations.',
                'provider_description' => 'Offer your skills and services to individual consumers in your local area.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // B2C (Business to Consumer) Configuration
        BusinessModelConfig::updateOrCreate(
            ['business_model' => 'b2c'],
            [
                'allowed_client_roles' => ['customer'],
                'allowed_provider_roles' => ['businessProvider'],
                'allowed_client_types' => ['individual'],
                'allowed_provider_types' => ['business', 'enterprise'],
                'supported_job_types' => [
                    'homeRepair',
                    'personalService',
                    'commercialRepair',
                    'installation',
                    'cleaning',
                    'catering',
                    'transportation'
                ],
                'supported_payment_methods' => [
                    'cash',
                    'bank_transfer',
                    'credit_card',
                    'debit_card',
                    'mobile_money',
                    'check'
                ],
                'supported_payment_schedules' => [
                    'immediate',
                    'net7',
                    'net15',
                    'completion'
                ],
                'minimum_transaction_amount' => 50.00,
                'maximum_transaction_amount' => 50000.00,
                'requires_contract' => true,
                'requires_invoice' => true,
                'requires_insurance' => true,
                'requires_license' => true,
                'requires_background_check' => true,
                'platform_fee_percentage' => 3.00,
                'platform_fee_fixed' => 0.00,
                'minimum_fee' => 1.50,
                'maximum_fee' => 1500.00,
                'enabled_features' => [
                    'review_system',
                    'payment_processing',
                    'dispute_resolution',
                    'contract_management',
                    'invoice_generation',
                    'insurance_verification'
                ],
                'description' => 'Individual consumers hiring businesses for professional services with enhanced guarantees.',
                'client_description' => 'Access professional business services with contracts, insurance, and quality guarantees.',
                'provider_description' => 'Offer your business services to individual consumers with professional standards.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // C2B (Consumer to Business) Configuration
        BusinessModelConfig::updateOrCreate(
            ['business_model' => 'c2b'],
            [
                'allowed_client_roles' => ['businessCustomer'],
                'allowed_provider_roles' => ['fundi'],
                'allowed_client_types' => ['business', 'enterprise', 'government', 'nonprofit'],
                'allowed_provider_types' => ['individual'],
                'supported_job_types' => [
                    'consultation',
                    'training',
                    'consulting',
                    'digitalService',
                    'marketing',
                    'audit',
                    'compliance'
                ],
                'supported_payment_methods' => [
                    'bank_transfer',
                    'credit_card',
                    'check',
                    'wire_transfer',
                    'invoice'
                ],
                'supported_payment_schedules' => [
                    'net15',
                    'net30',
                    'net60',
                    'milestone',
                    'completion'
                ],
                'minimum_transaction_amount' => 100.00,
                'maximum_transaction_amount' => 100000.00,
                'requires_contract' => true,
                'requires_invoice' => true,
                'requires_insurance' => true,
                'requires_license' => true,
                'requires_background_check' => true,
                'platform_fee_percentage' => 4.00,
                'platform_fee_fixed' => 0.00,
                'minimum_fee' => 4.00,
                'maximum_fee' => 4000.00,
                'enabled_features' => [
                    'review_system',
                    'payment_processing',
                    'dispute_resolution',
                    'contract_management',
                    'invoice_generation',
                    'milestone_tracking',
                    'time_tracking'
                ],
                'description' => 'Businesses hiring individual professionals for specialized consulting and business services.',
                'client_description' => 'Hire skilled individual professionals for business consulting, training, and specialized services.',
                'provider_description' => 'Offer your professional expertise to businesses and organizations.',
                'is_active' => true,
                'is_featured' => false,
            ]
        );

        // B2B (Business to Business) Configuration
        BusinessModelConfig::updateOrCreate(
            ['business_model' => 'b2b'],
            [
                'allowed_client_roles' => ['businessCustomer'],
                'allowed_provider_roles' => ['businessProvider'],
                'allowed_client_types' => ['business', 'enterprise', 'government', 'nonprofit'],
                'allowed_provider_types' => ['business', 'enterprise'],
                'supported_job_types' => [
                    'construction',
                    'maintenance',
                    'installation',
                    'consulting',
                    'training',
                    'audit',
                    'compliance',
                    'digitalService',
                    'marketing',
                    'legal',
                    'accounting',
                    'hr',
                    'logistics',
                    'security',
                    'cleaning',
                    'catering',
                    'transportation',
                    'equipment',
                    'emergency'
                ],
                'supported_payment_methods' => [
                    'bank_transfer',
                    'credit_card',
                    'check',
                    'wire_transfer',
                    'invoice',
                    'escrow'
                ],
                'supported_payment_schedules' => [
                    'net30',
                    'net60',
                    'net90',
                    'milestone',
                    'completion'
                ],
                'minimum_transaction_amount' => 500.00,
                'maximum_transaction_amount' => 1000000.00,
                'requires_contract' => true,
                'requires_invoice' => true,
                'requires_insurance' => true,
                'requires_license' => true,
                'requires_background_check' => true,
                'platform_fee_percentage' => 2.50,
                'platform_fee_fixed' => 0.00,
                'minimum_fee' => 12.50,
                'maximum_fee' => 25000.00,
                'enabled_features' => [
                    'review_system',
                    'payment_processing',
                    'dispute_resolution',
                    'contract_management',
                    'invoice_generation',
                    'milestone_tracking',
                    'time_tracking',
                    'project_management',
                    'escrow_services',
                    'compliance_tracking'
                ],
                'description' => 'Businesses providing services to other businesses with comprehensive project management.',
                'client_description' => 'Access professional business services with comprehensive project management and compliance.',
                'provider_description' => 'Offer your business services to other businesses with enterprise-level features.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
    }
} 