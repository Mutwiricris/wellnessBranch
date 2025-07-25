# Wellness Spa Management System - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Core Features](#core-features)
4. [Booking Management System](#booking-management-system)
5. [Payment Processing](#payment-processing)
6. [User Management](#user-management)
7. [Staff Management](#staff-management)
8. [Analytics & Reporting](#analytics--reporting)
9. [Real-Time Notifications](#real-time-notifications)
10. [Security & Access Control](#security--access-control)
11. [Database Schema](#database-schema)
12. [API Endpoints](#api-endpoints)
13. [Installation Guide](#installation-guide)
14. [Configuration](#configuration)
15. [Troubleshooting](#troubleshooting)
16. [Development Guidelines](#development-guidelines)
17. [Future Enhancements](#future-enhancements)

---

## Project Overview

### System Information
- **Project Name**: Wellness Spa Management System
- **Version**: 2.0.0 - Enhanced Dashboard & Real-Time Notifications
- **Framework**: Laravel 12 with PHP 8.4
- **Database**: MySQL 8.0+
- **Frontend**: Livewire, DaisyUI, TailwindCSS, Alpine.js
- **Real-Time**: Laravel Echo + Pusher WebSocket
- **Architecture**: Branch-scoped MVC with role-based access control

### Purpose
A comprehensive spa and wellness center management platform designed for multi-branch operations with branch-level autonomy, real-time notifications, advanced booking management, and professional-grade features.

---

## System Architecture

### Technical Stack

#### Backend Components
- **Laravel 12**: Modern PHP framework with enhanced features
- **PHP 8.4**: Latest PHP with performance optimizations
- **MySQL**: Relational database with proper indexing
- **Laravel Echo**: Real-time broadcasting capabilities
- **Queue System**: Background job processing for notifications

#### Frontend Components
- **Livewire**: Dynamic interfaces without complex JavaScript
- **DaisyUI**: Professional Tailwind CSS component framework
- **Alpine.js**: Lightweight JavaScript framework
- **TailwindCSS**: Utility-first CSS framework
- **Heroicons**: Professional SVG icon system

#### Real-Time Infrastructure
- **Pusher Integration**: WebSocket connections for live updates
- **Event Broadcasting**: Server-side events with client listeners
- **Channel Authorization**: Branch-scoped private channels
- **Fallback Mechanisms**: Polling support when WebSocket unavailable

---

## Core Features

### 1. Advanced Booking Management
- **Multi-Step Booking Flow**: Service → Staff → DateTime → Client Info
- **Real-time Calendar**: Drag-and-drop scheduling with conflict detection
- **Smart Scheduling**: Automatic time slot generation with availability checking
- **Status Workflow**: Pending → Confirmed → In Progress → Completed
- **Payment-Gated Progression**: Cannot confirm/complete without payment

### 2. Comprehensive Payment System
- **Multiple Payment Methods**: Cash, Card, M-Pesa, Bank Transfer
- **Payment Validation**: Smart booking status changes based on payment
- **Transaction Tracking**: Complete payment history with references
- **Revenue Analytics**: Daily, weekly, monthly tracking with growth indicators

### 3. Staff Management
- **Multi-Branch Assignment**: Staff can work across multiple branches
- **Schedule Management**: Comprehensive availability tracking
- **Color Coding**: Visual staff identification in calendar views
- **Performance Metrics**: Utilization tracking and analytics

### 4. Real-Time Notification System
- **Audio Notifications**: 7 different sounds for various events
- **Visual Alerts**: Toast notifications with customizable settings
- **Browser Notifications**: Native notification support
- **End-of-Day Reports**: Automated daily summaries

### 5. Enhanced Dashboard & Analytics
- **Real-Time Statistics**: Live booking updates and revenue tracking
- **Trend Indicators**: Growth percentages with visual indicators
- **Business Insights**: Popular services, peak hours, client retention
- **Performance Summaries**: Weekly/monthly completion rates

---

## Booking Management System

### Booking Workflow

#### Three-Step Process
1. **Payment Recording** (`payment_status: pending → completed`)
   - Creates detailed Payment record with transaction references
   - Updates booking payment_status to 'completed'
   - **Required before** booking confirmation or completion

2. **Booking Confirmation** (`status: pending → confirmed`)
   - Changes booking status from 'pending' to 'confirmed'
   - **Only allowed** if `payment_status === 'completed'`
   - Uses `hasValidPayment()` validation

3. **Service Completion** (`status: in_progress → completed`)
   - Marks service as delivered and finished
   - **Only allowed** if payment is completed
   - Auto-creates payment record if none exists

### Booking Statuses
- **pending**: Initial booking state, awaiting payment
- **confirmed**: Payment completed, booking approved
- **in_progress**: Service currently being delivered
- **completed**: Service finished successfully
- **cancelled**: Booking cancelled by client or staff
- **no_show**: Client didn't arrive for appointment

### Payment Statuses
- **pending**: Payment not yet received
- **completed**: Payment successfully processed
- **failed**: Payment attempt failed
- **refunded**: Payment returned to client

### Validation Rules
```php
// Booking can only be confirmed if payment is completed
public function canBeConfirmed(): bool
{
    return $this->status === 'pending' && $this->hasValidPayment();
}

// Service can only be completed if payment is valid
public function canBeCompletedWithPayment(): bool
{
    return $this->status === 'in_progress' && $this->hasValidPayment();
}
```

### UI Features
- **Workflow Progress Indicators**: Visual 3-step progress tracking
- **Payment Progress Bar**: Shows payment completion percentage
- **Next Action Highlighting**: Prominent display of required next step
- **Context-Sensitive Buttons**: Actions only appear when appropriate
- **Smart Validation**: JavaScript prevents invalid status transitions

---

## Payment Processing

### Payment Methods Supported
1. **Cash**: Direct cash payments with receipt generation
2. **Card**: Credit/debit card payments with transaction references
3. **M-Pesa**: Mobile money integration with transaction IDs
4. **Bank Transfer**: Direct bank transfers with reference tracking

### Payment Data Structure
```php
// payments table
id, booking_id, amount, payment_method, transaction_reference,
mpesa_checkout_request_id, status, processed_at, created_at, updated_at

// bookings table payment fields
payment_status, payment_method, mpesa_transaction_id
```

### Payment Workflow
1. **Payment Recording**: Staff records payment details via modal
2. **Validation**: System validates required fields based on method
3. **Status Update**: Both Payment record and Booking payment_status updated
4. **Notification**: Real-time notification sent to relevant parties
5. **Progress Update**: UI progress indicators reflect new status

### Payment Icons & Methods
- **Cash**: Dollar sign SVG icon
- **Card**: Credit card SVG icon  
- **M-Pesa**: Mobile phone SVG icon
- **Bank Transfer**: Bank building SVG icon

---

## User Management

### User Roles & Permissions

#### Branch Manager
- **Complete Access**: Full control over assigned branch operations
- **Branch Scoped**: Cannot access other branches or system settings
- **Responsibilities**: Bookings, payments, staff, coupons, reports, inventory

#### User Types
- **regular**: Standard spa clients
- **vip**: Premium clients with special privileges
- **corporate**: Business clients with bulk bookings
- **walk_in**: Clients without prior booking

### Client Profile System
```php
// Enhanced client data structure
class Client extends Model {
    // Demographics
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
        'gender', 'address', 'emergency_contact', 'preferred_language'
    ];
    
    // Preferences & Medical
    protected $casts = [
        'service_preferences' => 'array',
        'allergies' => 'array',
        'skin_conditions' => 'array',
        'treatment_notes' => 'array'
    ];
    
    // Loyalty & Analytics
    'loyalty_points', 'loyalty_tier', 'total_spent', 'visit_count',
    'last_visit_date', 'no_show_count', 'cancellation_count'
}
```

---

## Staff Management

### Staff Features
- **Multi-Branch Assignment**: Staff can be assigned to multiple branches
- **Schedule Management**: Comprehensive availability tracking
- **Color Coding**: Visual identification in calendar systems
- **Performance Tracking**: Service counts, ratings, utilization rates
- **Commission Tracking**: Earnings per service (future implementation)

### Staff Data Structure
```php
// staff table
id, name, email, phone, branch_id, specializations, color,
availability_schedule, hourly_rate, commission_rate, status
```

### Staff Assignment
- **Service Specialization**: Staff can be specialized for specific services
- **Availability Windows**: Define working hours per staff member
- **Automatic Assignment**: System can auto-assign based on availability
- **Manual Override**: Branch managers can manually assign staff

---

## Analytics & Reporting

### Dashboard Metrics

#### Today's Statistics
- **Total Bookings**: Count with pending breakdown
- **Completed Services**: Count with confirmed breakdown  
- **Daily Revenue**: Amount with growth percentage vs yesterday
- **Staff Utilization**: Percentage with active staff count
- **No-Show Rate**: Percentage with total no-show count
- **Average Service Time**: Minutes with upcoming count

#### Financial Metrics
- **Weekly Revenue**: Total with growth indicators
- **Monthly Revenue**: Total with trend analysis
- **Pending Payments**: Amount and booking count
- **Client Retention**: Percentage with repeat client ratio

#### Business Intelligence
- **Popular Services**: Top services by booking count and revenue
- **Peak Hours**: Busiest time slots with booking counts
- **Staff Performance**: Individual metrics and comparisons
- **Client Analytics**: Visit patterns and loyalty metrics

### Report Types
1. **Detailed Report**: Complete booking information with client details
2. **Summary Report**: Overview statistics and key metrics
3. **Financial Report**: Revenue, payments, and financial analytics
4. **Staff Report**: Performance metrics and utilization rates

---

## Real-Time Notifications

### Notification System
- **Audio Alerts**: 7 different notification sounds
- **Visual Notifications**: Toast messages with color coding
- **Browser Notifications**: Native OS notification integration
- **Notification Center**: History with read/unread status

### Sound Files
```
/public/sounds/
├── notification-confirmed.wav    # Booking confirmation
├── notification-cancelled.wav    # Booking cancellation  
├── notification-completed.wav    # Service completion
├── notification-payment.wav      # Payment received
├── notification-error.wav        # Error notifications
├── notification-success.wav      # Success notifications
└── notification-warning.wav      # Warning notifications
```

### Event Types
- **Booking Confirmed**: When booking status changes to confirmed
- **Booking Cancelled**: When booking is cancelled
- **Service Completed**: When service status changes to completed
- **Payment Received**: When payment is recorded
- **End-of-Day Report**: Daily summary notifications

### WebSocket Channels
```php
// Private channels per branch
Private Channels:
- branch.{branch_id}.bookings
- branch.{branch_id}.end-of-day
- admin.bookings (for system admins)
```

---

## Security & Access Control

### Authentication & Authorization
- **Branch Scoping**: All data automatically scoped to manager's branch
- **Cross-Branch Prevention**: Middleware prevents unauthorized access
- **Role-Based Access**: Permissions based on user role
- **Secure API Endpoints**: CSRF protection and authentication required

### Data Security
- **Branch Isolation**: Complete data separation between branches
- **Encrypted Sensitive Data**: Client information and payment details
- **Audit Logging**: All actions logged for security tracking
- **GDPR Compliance**: Data processing consent and privacy controls

### Middleware Protection
```php
// All controller actions protected
$this->middleware('auth:branch_manager');
$this->middleware('branch_scope'); // Ensures branch_id filtering
```

---

## Database Schema

### Core Tables

#### bookings
```sql
id, booking_reference, branch_id, service_id, client_id, staff_id,
appointment_date, start_time, end_time, status, notes, total_amount,
payment_status, payment_method, mpesa_transaction_id, 
cancellation_reason, cancelled_at, confirmed_at, created_at, updated_at
```

#### payments
```sql
id, booking_id, amount, payment_method, transaction_reference,
mpesa_checkout_request_id, status, processed_at, created_at, updated_at
```

#### clients (Enhanced User Model)
```sql
id, first_name, last_name, email, phone, phone_secondary, gender,
date_of_birth, address_line_1, address_line_2, city, state, postal_code,
country, emergency_contact_name, emergency_contact_phone,
allergies, medical_conditions, skin_type, service_preferences,
communication_preferences, profile_picture, status, client_type,
acquisition_source, loyalty_points, loyalty_tier, total_spent,
visit_count, last_visit_date, no_show_count, cancellation_count
```

#### staff
```sql
id, name, email, phone, branch_id, specializations, color,
availability_schedule, hourly_rate, commission_rate, status,
created_at, updated_at
```

### Relationships
- **Bookings** belong to Branch, Service, Client (User), Staff
- **Payments** belong to Booking
- **Staff** can belong to multiple Branches
- **Services** belong to Branch
- **Clients** can have multiple Bookings

---

## API Endpoints

### Booking Management
```php
// Booking operations
GET    /branch/bookings              # List all bookings
POST   /branch/bookings              # Create new booking
GET    /branch/bookings/{id}         # Show booking details
PATCH  /branch/bookings/{id}/status  # Update booking status
POST   /branch/bookings/{id}/payment # Record payment
PATCH  /branch/bookings/{id}/assign-staff # Assign staff
```

### Payment Operations
```php
// Payment processing
POST   /branch/bookings/{id}/payment # Record payment
GET    /branch/payments              # List payments
GET    /branch/payments/{id}         # Payment details
```

### Analytics & Reports
```php
// Dashboard and analytics
GET    /branch/dashboard             # Dashboard statistics
GET    /api/end-of-day-report        # End of day report
GET    /api/end-of-day-report/export # Export reports (PDF/CSV)
```

### Real-Time Endpoints
```php
// WebSocket channels
Private: branch.{branch_id}.bookings
Private: branch.{branch_id}.end-of-day
Private: admin.bookings
```

---

## Installation Guide

### System Requirements
- PHP 8.4+
- MySQL 8.0+
- Node.js 18+
- Composer 2.6+
- Redis (recommended for queues and caching)

### Installation Steps

#### 1. Clone Repository
```bash
git clone <repository-url>
cd wellness_branch
```

#### 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

#### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

#### 4. Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wellness_spa
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 5. Database Migration
```bash
php artisan migrate
php artisan db:seed
```

#### 6. Broadcasting Setup (Optional)
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_CLUSTER=your_pusher_cluster
```

#### 7. Sound System Setup
```bash
chmod +x public/sounds/setup-sounds.sh
./public/sounds/setup-sounds.sh
```

#### 8. Start Development Server
```bash
php artisan serve
```

---

## Configuration

### Environment Variables
```env
# Application
APP_NAME="Wellness Spa Management"
APP_ENV=production
APP_KEY=base64:generated_key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wellness_spa
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Broadcasting (WebSocket)
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_ID=your_id
PUSHER_APP_CLUSTER=your_cluster

# Queue System
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Branch Settings
Branch managers can configure:
- Booking window hours (e.g., 9AM–8PM)
- Available payment methods for POS
- SMS/WhatsApp sender configuration
- Custom welcome messages
- Theme and branding options

---

## Troubleshooting

### Common Issues

#### 1. Notification Sounds Not Playing
**Symptoms**: Audio notifications not working
**Solutions**:
- Check browser autoplay policy settings
- Ensure sound files are properly formatted (WAV/MP3)
- Verify volume settings in notification center
- Check file permissions in `public/sounds/` directory

#### 2. Real-Time Notifications Not Working
**Symptoms**: Live updates not appearing
**Solutions**:
- Verify Pusher credentials in `.env` file
- Check browser console for WebSocket errors
- Ensure private channels are properly authorized
- Test WebSocket connection manually

#### 3. Database Connection Issues
**Symptoms**: Application cannot connect to database
**Solutions**:
- Verify MySQL credentials in `.env` file
- Check database server status
- Run `php artisan migrate:status` to verify migrations
- Ensure MySQL service is running

#### 4. Payment Workflow Issues
**Symptoms**: Cannot confirm bookings after payment recording
**Solutions**:
- Check payment status in database
- Verify `hasValidPayment()` method logic
- Ensure payment record exists in payments table
- Check booking payment_status field

#### 5. Permission Denied Errors
**Symptoms**: 403 errors or access denied messages
**Solutions**:
- Verify branch manager role assignment
- Check branch_id matching in middleware
- Ensure proper authentication
- Verify user permissions in database

---

## Development Guidelines

### Code Standards
- Follow PSR-12 PHP coding standards
- Use TypeScript for complex frontend logic
- Implement comprehensive testing (PHPUnit + Pest)
- Document all API endpoints with OpenAPI
- Use meaningful variable and method names

### Performance Standards
- Page load time < 2 seconds
- API response time < 500ms
- 95% uptime requirement
- Mobile-first responsive design
- Optimized database queries with proper indexing.


### Security Standards
- OWASP compliance for web applications
- Regular security audits and updates
- Data encryption at rest and in transit
- GDPR compliance for client data
- Secure API authentication and authorization

### Git Workflow
1. Create feature branches from main
2. Use descriptive commit messages
3. Include tests for new functionality
4. Document API changes
5. Request code review before merging

---

## Future Enhancements

### Phase 1: Client Experience (High Priority)
- **360° Client Profile System**: Comprehensive client profiling
- **Automated Marketing**: Email/SMS workflows and follow-ups
- **Online Booking Portal**: Client self-service booking interface
- **Mobile App**: React Native or Flutter application

### Phase 2: Business Intelligence (Medium Priority)
- **Advanced Dashboard Widgets**: Customizable KPI widgets
- **Predictive Analytics**: Demand forecasting and pricing optimization
- **Multi-Location Management**: Cross-branch analytics and management
- **AI-Powered Insights**: Machine learning for business optimization

### Phase 3: Operational Excellence (Medium Priority)
- **Waitlist Management**: Smart waitlist with automated notifications
- **Inventory Management**: Product tracking with reorder alerts
- **POS System Enhancement**: Full retail functionality integration
- **Staff Performance Analytics**: Advanced metrics and reporting

### Phase 4: Advanced Features (Lower Priority)
- **Loyalty Program**: Points-based reward system
- **Review & Rating System**: Client feedback management
- **Gift Card System**: QR code-based gift certificates
- **Integration APIs**: Third-party service integrations

### Technical Roadmap
- **Performance Optimization**: Redis caching and query optimization
- **API Enhancement**: RESTful API expansion for third-party integrations
- **Mobile Application**: Native iOS and Android applications
- **Advanced Analytics**: Machine learning integration for insights

---

## Support & Maintenance

### Documentation Updates
This documentation should be updated with each major release to reflect:
- New features and functionality
- API endpoint changes
- Configuration updates
- Security enhancements
- Bug fixes and improvements

### Version Control
- **Major Version**: Significant feature additions or breaking changes
- **Minor Version**: New features that maintain backward compatibility
- **Patch Version**: Bug fixes and minor improvements

### Support Channels
- GitHub Issues: Bug reports and feature requests
- Documentation: Comprehensive guides and troubleshooting
- Community: User forums and discussion groups

---

**Last Updated**: July 23, 2025  
**Version**: 2.0.0  
**Prepared By**: Development Team  
**Built with excellence for wellness professionals worldwide**