# Business Model Implementation Documentation

## Overview

The Fundi API has been enhanced to support all four major business models:
- **C2C (Consumer to Consumer)**: Individual consumers hiring individual service providers
- **B2C (Business to Consumer)**: Individual consumers hiring businesses for services
- **C2B (Consumer to Business)**: Businesses hiring individual professionals
- **B2B (Business to Business)**: Businesses providing services to other businesses

## Architecture Changes

### 1. Enhanced User Entity

#### New User Roles
```php
// Individual roles
'client'             // C2C, B2C: Individual seeking services
'fundi'              // C2C, C2B: Individual providing services

// Business roles
'businessClient'     // B2B, B2C: Business seeking services
'businessProvider'   // B2B, C2B: Business providing services

// Platform roles
'admin'              // Platform administrator
'moderator'          // Platform moderator
'support'            // Customer support
```

#### New User Types
```php
'individual'         // C2C, C2B, B2C
'business'           // B2B, B2C, C2B
'enterprise'         // Large business (B2B)
'government'         // Government entity (B2B)
'nonprofit'          // Non-profit organization (B2B)
```

#### Business Profile Fields
- Business name, type, registration number
- Tax ID, website, description
- Services, industries, employee count
- Year established, license number
- Certifications, address, contact persons
- Business hours, payment methods
- Average project value, completed projects
- Rating and reviews count

#### Individual Profile Fields
- Bio, location, skills, specializations
- Hourly/daily/project rates
- Certifications, years of experience
- Languages, availability
- Preferred job types, portfolio
- Rating, reviews count, completed jobs

### 2. Enhanced Job Entity

#### New Job Types
```php
// Individual jobs (C2C, B2C)
'homeRepair'         // Home repair and maintenance
'personalService'    // Personal services (cleaning, etc.)
'eventService'       // Event-related services
'consultation'       // Professional consultation

// Business jobs (B2B, C2B)
'commercialRepair'   // Commercial property repair
'construction'       // Construction projects
'maintenance'        // Ongoing maintenance contracts
'installation'       // Equipment installation
'consulting'         // Business consulting
'training'           // Staff training
'audit'              // Business audits
'compliance'         // Regulatory compliance
'digitalService'     // Digital/IT services
'marketing'          // Marketing services
'legal'              // Legal services
'accounting'         // Accounting services
'hr'                 // Human resources
'logistics'          // Logistics and supply chain
'security'           // Security services
'cleaning'           // Commercial cleaning
'catering'           // Corporate catering
'transportation'     // Transportation services
'equipment'          // Equipment rental/repair
'emergency'          // Emergency services
```

#### Business Model Support
```php
'c2c'    // Consumer to Consumer
'b2c'    // Business to Consumer
'c2b'    // Consumer to Business
'b2b'    // Business to Business
```

#### Enhanced Job Features
- **Job Requirements**: Skills, certifications, experience, tools, insurance
- **Job Timeline**: Start/end dates, milestones, onsite requirements
- **Job Payment**: Multiple payment types, methods, schedules
- **Metadata**: Tags, urgency, featured status, view/proposal counts

### 3. Business Model Configuration

#### Configuration Features
- **Allowed Roles**: Which user roles can be clients/providers
- **Allowed Types**: Which user types are supported
- **Supported Job Types**: Job types available for each model
- **Payment Methods**: Supported payment methods per model
- **Payment Schedules**: Supported payment schedules per model
- **Requirements**: Contract, invoice, insurance, license requirements
- **Financial Limits**: Minimum/maximum transaction amounts
- **Platform Fees**: Different fee structures per model

## API Endpoints

### Business Model Endpoints

#### 1. Get All Business Models
```http
GET /api/v1/business-models
```
Returns all active business model configurations.

#### 2. Get Specific Business Model
```http
GET /api/v1/business-models/{business_model}
```
Returns configuration for a specific business model (c2c, b2c, c2b, b2b).

#### 3. Check User Compatibility
```http
POST /api/v1/business-models/{business_model}/check-compatibility
```
Check if the authenticated user can participate in a business model.

**Request Body:**
```json
{
    "participation_type": "client|provider"
}
```

**Response:**
```json
{
    "compatible": true,
    "message": "User can be a client in this business model",
    "requirements": ["Contract required", "Insurance required"],
    "missing_requirements": ["User verification required for contracts"],
    "business_model_config": {...}
}
```

#### 4. Get Jobs by Business Model
```http
GET /api/v1/business-models/{business_model}/jobs
```
Get jobs filtered by business model with optional filters.

**Query Parameters:**
- `job_type`: Filter by job type
- `payment_type`: Filter by payment type
- `urgency`: Filter by urgency
- `min_budget`: Minimum budget
- `max_budget`: Maximum budget

#### 5. Calculate Platform Fee
```http
POST /api/v1/business-models/{business_model}/calculate-fee
```
Calculate platform fee for a transaction amount.

**Request Body:**
```json
{
    "amount": 1000.00
}
```

**Response:**
```json
{
    "amount": 1000.00,
    "platform_fee": 50.00,
    "net_amount": 950.00,
    "fee_breakdown": {
        "percentage_fee": 50.00,
        "fixed_fee": 0.00,
        "percentage_rate": 5.00,
        "fixed_rate": 0.00
    }
}
```

#### 6. Business Model Dashboard
```http
GET /api/v1/business-models/dashboard
```
Get dashboard data showing user compatibility with all business models.

### Enhanced Job Endpoints

#### Create Job (Enhanced)
```http
POST /api/v1/jobs
```

**Request Body:**
```json
{
    "title": "Home Plumbing Repair",
    "description": "Need urgent plumbing repair",
    "detailed_description": "Detailed description of the plumbing issue...",
    "location": "123 Main St, City",
    "category_id": 1,
    "business_model": "c2c",
    "job_type": "homeRepair",
    "requirements": ["Licensed plumber", "Emergency service"],
    "skills_required": ["plumbing", "repair"],
    "experience_required": 2,
    "payment_type": "fixed",
    "fixed_amount": 150.00,
    "accepted_payment_methods": ["cash", "mobile_money"],
    "urgency": "urgent",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "city": "New York",
    "state": "NY",
    "country": "USA"
}
```

## Business Model Configurations

### C2C (Consumer to Consumer)
- **Clients**: Individual consumers
- **Providers**: Individual fundis
- **Job Types**: Home repair, personal services, events, consultation
- **Payment**: Cash, bank transfer, mobile money, credit card
- **Requirements**: Background check only
- **Fees**: 5% platform fee
- **Limits**: $10 - $10,000

### B2C (Business to Consumer)
- **Clients**: Individual consumers
- **Providers**: Businesses and enterprises
- **Job Types**: Home repair, personal services, commercial repair, installation
- **Payment**: Cash, bank transfer, credit/debit cards, mobile money, checks
- **Requirements**: Contract, invoice, insurance, license, background check
- **Fees**: 3% platform fee
- **Limits**: $50 - $50,000

### C2B (Consumer to Business)
- **Clients**: Businesses, enterprises, government, nonprofits
- **Providers**: Individual fundis
- **Job Types**: Consultation, training, consulting, digital services, marketing
- **Payment**: Bank transfer, credit cards, checks, wire transfer, invoices
- **Requirements**: Contract, invoice, insurance, license, background check
- **Fees**: 4% platform fee
- **Limits**: $100 - $100,000

### B2B (Business to Business)
- **Clients**: Businesses, enterprises, government, nonprofits
- **Providers**: Businesses and enterprises
- **Job Types**: All business services (construction, consulting, legal, etc.)
- **Payment**: Bank transfer, credit cards, checks, wire transfer, invoices, escrow
- **Requirements**: Contract, invoice, insurance, license, background check
- **Fees**: 2.5% platform fee
- **Limits**: $500 - $1,000,000

## Usage Examples

### C2C Example
```php
// Individual hiring individual for home repair
$job = Job::create([
    'business_model' => 'c2c',
    'job_type' => 'homeRepair',
    'payment_type' => 'fixed',
    'fixed_amount' => 150.00,
    'accepted_payment_methods' => ['cash', 'mobile_money'],
    'requires_background_check' => true,
]);
```

### B2B Example
```php
// Business hiring business for construction project
$job = Job::create([
    'business_model' => 'b2b',
    'job_type' => 'construction',
    'payment_type' => 'milestone',
    'accepted_payment_methods' => ['bank_transfer', 'invoice'],
    'payment_schedule' => 'net30',
    'requires_contract' => true,
    'requires_invoice' => true,
    'requires_insurance' => true,
    'requires_license' => true,
]);
```

## Database Migrations

### 1. Enhance Users Table
```bash
php artisan migrate --path=database/migrations/2024_12_19_000001_enhance_users_table_for_business_models.php
```

### 2. Enhance Jobs Table
```bash
php artisan migrate --path=database/migrations/2024_12_19_000002_enhance_jobs_table_for_business_models.php
```

### 3. Create Business Model Configs Table
```bash
php artisan migrate --path=database/migrations/2024_12_19_000003_create_business_model_configs_table.php
```

## Seeding Data

### Seed Business Model Configurations
```bash
php artisan db:seed --class=BusinessModelConfigSeeder
```

## Models

### User Model Enhancements
- New role and type constants
- Business model compatibility methods
- Profile completion tracking
- Enhanced scopes and relationships

### Job Model Enhancements
- Business model and job type constants
- Payment and budget management
- Location-based queries
- Enhanced filtering and scoping

### BusinessModelConfig Model
- Configuration management
- Compatibility checking
- Fee calculation
- Feature management

## Security & Validation

### Authorization Rules
- Role-based access control
- Business model compatibility checks
- User type validation
- Payment method validation

### Data Validation
- Comprehensive input validation
- Business rule enforcement
- Transaction limit checking
- Geographic validation

## Testing

### Unit Tests
- Business model configuration validation
- User role and type compatibility checks
- Payment method and schedule validation
- Job type and business model compatibility

### Integration Tests
- End-to-end job creation and booking flows
- Payment processing for different models
- User registration and role assignment
- Business profile creation and validation

## Future Enhancements

### 1. Advanced Features
- Multi-currency support
- Tax management
- Compliance tracking
- Performance analytics

### 2. Integration Features
- Accounting software integration
- Payment gateway integration
- Document management
- Communication tools

### 3. AI/ML Features
- Smart matching
- Pricing optimization
- Risk assessment
- Fraud detection

## Conclusion

The enhanced business model implementation provides a comprehensive, scalable, and flexible platform that supports all major business transaction types. The modular architecture allows for easy customization and extension while maintaining security and compliance requirements. 