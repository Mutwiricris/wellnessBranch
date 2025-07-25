/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `analytics_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `total_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `service_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `product_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `commission_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expenses` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `average_bill_value` decimal(8,2) NOT NULL DEFAULT '0.00',
  `total_bookings` int NOT NULL DEFAULT '0',
  `completed_bookings` int NOT NULL DEFAULT '0',
  `cancelled_bookings` int NOT NULL DEFAULT '0',
  `no_show_bookings` int NOT NULL DEFAULT '0',
  `online_bookings` int NOT NULL DEFAULT '0',
  `walk_in_bookings` int NOT NULL DEFAULT '0',
  `booking_conversion_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cancellation_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `new_clients` int NOT NULL DEFAULT '0',
  `returning_clients` int NOT NULL DEFAULT '0',
  `total_unique_clients` int NOT NULL DEFAULT '0',
  `client_retention_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `client_satisfaction_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `active_staff` int NOT NULL DEFAULT '0',
  `staff_utilization_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `average_staff_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `staff_efficiency_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `products_sold` int NOT NULL DEFAULT '0',
  `inventory_turnover_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `low_stock_items` int NOT NULL DEFAULT '0',
  `campaign_bookings` int NOT NULL DEFAULT '0',
  `marketing_roi` decimal(8,2) NOT NULL DEFAULT '0.00',
  `referral_bookings` int NOT NULL DEFAULT '0',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `peak_hour_utilization` decimal(5,2) NOT NULL DEFAULT '0.00',
  `equipment_usage_hours` int NOT NULL DEFAULT '0',
  `cost_per_service` decimal(8,2) NOT NULL DEFAULT '0.00',
  `profit_margin` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analytics_data_branch_id_date_unique` (`branch_id`,`date`),
  KEY `analytics_data_date_index` (`date`),
  KEY `analytics_data_branch_id_date_index` (`branch_id`,`date`),
  CONSTRAINT `analytics_data_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(8,2) NOT NULL,
  `payment_status` enum('pending','completed','partial','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','mpesa','card','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `mpesa_transaction_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_reference_unique` (`booking_reference`),
  UNIQUE KEY `unique_staff_appointment` (`staff_id`,`appointment_date`,`start_time`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_booking_reference_index` (`booking_reference`),
  KEY `bookings_branch_id_appointment_date_index` (`branch_id`,`appointment_date`),
  KEY `bookings_staff_id_appointment_date_index` (`staff_id`,`appointment_date`),
  KEY `bookings_client_id_status_index` (`client_id`,`status`),
  KEY `bookings_appointment_date_start_time_index` (`appointment_date`,`start_time`),
  KEY `bookings_status_index` (`status`),
  KEY `bookings_payment_status_index` (`payment_status`),
  CONSTRAINT `bookings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `custom_price` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_services_branch_id_service_id_unique` (`branch_id`,`service_id`),
  KEY `branch_services_service_id_foreign` (`service_id`),
  KEY `branch_services_is_available_index` (`is_available`),
  CONSTRAINT `branch_services_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `staff_id` bigint unsigned NOT NULL,
  `working_hours` json NOT NULL,
  `is_primary_branch` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_staff_branch_id_staff_id_unique` (`branch_id`,`staff_id`),
  KEY `branch_staff_staff_id_foreign` (`staff_id`),
  KEY `branch_staff_is_primary_branch_index` (`is_primary_branch`),
  CONSTRAINT `branch_staff_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_staff_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `working_hours` json NOT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Africa/Nairobi',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branches_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_secondary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address_line_1` text COLLATE utf8mb4_unicode_ci,
  `address_line_2` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Kenya',
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` json DEFAULT NULL,
  `medical_conditions` json DEFAULT NULL,
  `skin_type` json DEFAULT NULL,
  `service_preferences` json DEFAULT NULL,
  `communication_preferences` json DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT '0',
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `client_type` enum('regular','vip','corporate','walk_in') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `acquisition_source` enum('referral','social_media','google','walk_in','advertisement','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loyalty_points` int NOT NULL DEFAULT '0',
  `loyalty_tier` enum('bronze','silver','gold','platinum','diamond') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `total_spent` decimal(12,2) NOT NULL DEFAULT '0.00',
  `visit_count` int NOT NULL DEFAULT '0',
  `last_visit_date` date DEFAULT NULL,
  `no_show_count` int NOT NULL DEFAULT '0',
  `cancellation_count` int NOT NULL DEFAULT '0',
  `marketing_consent` tinyint(1) NOT NULL DEFAULT '0',
  `sms_consent` tinyint(1) NOT NULL DEFAULT '1',
  `email_consent` tinyint(1) NOT NULL DEFAULT '1',
  `call_consent` tinyint(1) NOT NULL DEFAULT '1',
  `preferred_communication_times` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `tags` json DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `privacy_policy_accepted_at` timestamp NULL DEFAULT NULL,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`),
  UNIQUE KEY `clients_referral_code_unique` (`referral_code`),
  KEY `clients_email_index` (`email`),
  KEY `clients_phone_index` (`phone`),
  KEY `clients_status_index` (`status`),
  KEY `clients_loyalty_tier_index` (`loyalty_tier`),
  KEY `clients_last_visit_date_index` (`last_visit_date`),
  KEY `clients_client_type_index` (`client_type`),
  FULLTEXT KEY `clients_first_name_last_name_email_phone_fulltext` (`first_name`,`last_name`,`email`,`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discount_coupon_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `pos_transaction_id` bigint unsigned DEFAULT NULL,
  `discount_amount` decimal(8,2) NOT NULL,
  `used_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_usages_customer_id_foreign` (`customer_id`),
  KEY `coupon_usages_discount_coupon_id_customer_id_index` (`discount_coupon_id`,`customer_id`),
  KEY `coupon_usages_pos_transaction_id_index` (`pos_transaction_id`),
  CONSTRAINT `coupon_usages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coupon_usages_discount_coupon_id_foreign` FOREIGN KEY (`discount_coupon_id`) REFERENCES `discount_coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_pos_transaction_id_foreign` FOREIGN KEY (`pos_transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discount_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `coupon_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_type` enum('percentage','fixed_amount') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(8,2) NOT NULL,
  `minimum_order_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `maximum_discount_amount` decimal(8,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_customer` int NOT NULL DEFAULT '1',
  `used_count` int NOT NULL DEFAULT '0',
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('active','inactive','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `applicable_services` json DEFAULT NULL,
  `applicable_categories` json DEFAULT NULL,
  `customer_restrictions` json DEFAULT NULL,
  `time_restrictions` json DEFAULT NULL,
  `stackable` tinyint(1) NOT NULL DEFAULT '0',
  `created_by_staff_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_coupons_coupon_code_unique` (`coupon_code`),
  KEY `discount_coupons_created_by_staff_id_foreign` (`created_by_staff_id`),
  KEY `discount_coupons_branch_id_status_index` (`branch_id`,`status`),
  KEY `discount_coupons_coupon_code_index` (`coupon_code`),
  KEY `discount_coupons_expires_at_status_index` (`expires_at`,`status`),
  KEY `discount_coupons_starts_at_expires_at_index` (`starts_at`,`expires_at`),
  CONSTRAINT `discount_coupons_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_coupons_created_by_staff_id_foreign` FOREIGN KEY (`created_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subcategory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `approved_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `recurring_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_approved_by_foreign` (`approved_by`),
  KEY `expenses_branch_id_expense_date_index` (`branch_id`,`expense_date`),
  KEY `expenses_category_subcategory_index` (`category`,`subcategory`),
  KEY `expenses_status_index` (`status`),
  CONSTRAINT `expenses_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gift_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `voucher_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_amount` decimal(10,2) NOT NULL,
  `remaining_amount` decimal(10,2) NOT NULL,
  `applicable_services` json DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchaser_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchaser_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchaser_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `purchase_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','redeemed','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `sold_by_staff_id` bigint unsigned DEFAULT NULL,
  `commission_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `redemption_history` json DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gift_vouchers_voucher_code_unique` (`voucher_code`),
  KEY `gift_vouchers_sold_by_staff_id_foreign` (`sold_by_staff_id`),
  KEY `gift_vouchers_branch_id_status_index` (`branch_id`,`status`),
  KEY `gift_vouchers_voucher_code_index` (`voucher_code`),
  KEY `gift_vouchers_expiry_date_status_index` (`expiry_date`,`status`),
  CONSTRAINT `gift_vouchers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gift_vouchers_sold_by_staff_id_foreign` FOREIGN KEY (`sold_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cost_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `minimum_stock` int NOT NULL DEFAULT '10',
  `maximum_stock` int DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT '10.00',
  `track_expiry` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_items_sku_unique` (`sku`),
  KEY `inventory_items_branch_id_category_index` (`branch_id`,`category`),
  KEY `inventory_items_current_stock_minimum_stock_index` (`current_stock`,`minimum_stock`),
  KEY `inventory_items_is_active_index` (`is_active`),
  CONSTRAINT `inventory_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint DEFAULT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `transaction_date` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_staff_id_foreign` (`staff_id`),
  KEY `inventory_transactions_inventory_item_id_transaction_date_index` (`inventory_item_id`,`transaction_date`),
  KEY `inventory_transactions_branch_id_transaction_type_index` (`branch_id`,`transaction_type`),
  KEY `inventory_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `inventory_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','bonus','penalty') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL,
  `monetary_value` decimal(8,2) DEFAULT NULL,
  `pos_transaction_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','used') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_points_branch_id_foreign` (`branch_id`),
  KEY `loyalty_points_pos_transaction_id_foreign` (`pos_transaction_id`),
  KEY `loyalty_points_customer_id_branch_id_index` (`customer_id`,`branch_id`),
  KEY `loyalty_points_transaction_type_status_index` (`transaction_type`,`status`),
  KEY `loyalty_points_expiry_date_status_index` (`expiry_date`,`status`),
  CONSTRAINT `loyalty_points_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_points_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_points_pos_transaction_id_foreign` FOREIGN KEY (`pos_transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `processing_fee` decimal(10,2) DEFAULT NULL,
  `net_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('cash','mpesa','card','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_checkout_request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_booking_id_index` (`booking_id`),
  KEY `payments_status_index` (`status`),
  KEY `payments_transaction_reference_index` (`transaction_reference`),
  KEY `payments_mpesa_checkout_request_id_index` (`mpesa_checkout_request_id`),
  KEY `payments_branch_id_index` (`branch_id`),
  CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_daily_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_daily_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `summary_date` date NOT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `total_transactions` int NOT NULL DEFAULT '0',
  `completed_transactions` int NOT NULL DEFAULT '0',
  `failed_transactions` int NOT NULL DEFAULT '0',
  `refunded_transactions` int NOT NULL DEFAULT '0',
  `total_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mpesa_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `card_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bank_transfer_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `service_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `product_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `service_count` int NOT NULL DEFAULT '0',
  `product_count` int NOT NULL DEFAULT '0',
  `total_discounts` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tips` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `staff_performance` json DEFAULT NULL,
  `service_performance` json DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closing_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_daily_summaries_branch_id_summary_date_unique` (`branch_id`,`summary_date`),
  KEY `pos_daily_summaries_staff_id_foreign` (`staff_id`),
  KEY `pos_daily_summaries_summary_date_is_closed_index` (`summary_date`,`is_closed`),
  CONSTRAINT `pos_daily_summaries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_daily_summaries_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_payment_splits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_payment_splits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_transaction_id` bigint unsigned NOT NULL,
  `payment_method` enum('cash','mpesa','card','bank_transfer','gift_voucher','loyalty_points') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_details` json DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_payment_splits_pos_transaction_id_index` (`pos_transaction_id`),
  KEY `pos_payment_splits_payment_method_status_index` (`payment_method`,`status`),
  CONSTRAINT `pos_payment_splits_pos_transaction_id_foreign` FOREIGN KEY (`pos_transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `promotion_type` enum('happy_hour','seasonal','package_deal','loyalty_bonus','referral') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_type` enum('percentage','fixed_amount','buy_one_get_one') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(8,2) DEFAULT NULL,
  `applicable_services` json DEFAULT NULL,
  `applicable_categories` json DEFAULT NULL,
  `time_restrictions` json DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('active','inactive','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `auto_apply` tinyint(1) NOT NULL DEFAULT '0',
  `priority` int NOT NULL DEFAULT '0',
  `created_by_staff_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_promotions_created_by_staff_id_foreign` (`created_by_staff_id`),
  KEY `pos_promotions_branch_id_status_index` (`branch_id`,`status`),
  KEY `pos_promotions_promotion_type_status_index` (`promotion_type`,`status`),
  KEY `pos_promotions_starts_at_expires_at_index` (`starts_at`,`expires_at`),
  KEY `pos_promotions_auto_apply_status_index` (`auto_apply`,`status`),
  CONSTRAINT `pos_promotions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_promotions_created_by_staff_id_foreign` FOREIGN KEY (`created_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_transaction_id` bigint unsigned NOT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_type` enum('digital','printed','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'digital',
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_method` enum('sms','email','whatsapp') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivered` tinyint(1) NOT NULL DEFAULT '0',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `delivery_details` json DEFAULT NULL,
  `receipt_data` json NOT NULL,
  `receipt_html` text COLLATE utf8mb4_unicode_ci,
  `receipt_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_attempts` int NOT NULL DEFAULT '0',
  `last_delivery_attempt` timestamp NULL DEFAULT NULL,
  `delivery_errors` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_receipts_receipt_number_unique` (`receipt_number`),
  KEY `pos_receipts_pos_transaction_id_receipt_type_index` (`pos_transaction_id`,`receipt_type`),
  KEY `pos_receipts_delivered_delivery_method_index` (`delivered`,`delivery_method`),
  CONSTRAINT `pos_receipts_pos_transaction_id_foreign` FOREIGN KEY (`pos_transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_transaction_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_transaction_id` bigint unsigned NOT NULL,
  `item_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` bigint NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb4_unicode_ci,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL,
  `assigned_staff_id` bigint unsigned DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `service_start_time` timestamp NULL DEFAULT NULL,
  `service_end_time` timestamp NULL DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_transaction_items_assigned_staff_id_foreign` (`assigned_staff_id`),
  KEY `pos_transaction_items_pos_transaction_id_item_type_index` (`pos_transaction_id`,`item_type`),
  KEY `pos_transaction_items_item_type_item_id_index` (`item_type`,`item_id`),
  CONSTRAINT `pos_transaction_items_assigned_staff_id_foreign` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pos_transaction_items_pos_transaction_id_foreign` FOREIGN KEY (`pos_transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `staff_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `transaction_type` enum('service','product','package','mixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon_discount_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `voucher_discount_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `loyalty_points_used` int NOT NULL DEFAULT '0',
  `loyalty_points_earned` int NOT NULL DEFAULT '0',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tip_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','mpesa','card','bank_transfer','mixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `payment_details` json DEFAULT NULL,
  `mpesa_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_sent` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `customer_info` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_transactions_transaction_number_unique` (`transaction_number`),
  KEY `pos_transactions_client_id_foreign` (`client_id`),
  KEY `pos_transactions_booking_id_foreign` (`booking_id`),
  KEY `pos_transactions_branch_id_created_at_index` (`branch_id`,`created_at`),
  KEY `pos_transactions_staff_id_created_at_index` (`staff_id`,`created_at`),
  KEY `pos_transactions_payment_status_payment_method_index` (`payment_status`,`payment_method`),
  KEY `pos_transactions_transaction_number_index` (`transaction_number`),
  CONSTRAINT `pos_transactions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pos_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_transactions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pos_transactions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_categories_slug_unique` (`slug`),
  KEY `service_categories_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(8,2) NOT NULL,
  `duration_minutes` int NOT NULL,
  `buffer_time_minutes` int NOT NULL DEFAULT '10',
  `max_advance_booking_days` int NOT NULL DEFAULT '30',
  `requires_consultation` tinyint(1) NOT NULL DEFAULT '0',
  `is_couple_service` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_category_id_status_index` (`category_id`,`status`),
  KEY `services_price_index` (`price`),
  CONSTRAINT `services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialties` json DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience_years` int NOT NULL DEFAULT '0',
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#007bff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_email_unique` (`email`),
  KEY `staff_status_index` (`status`),
  KEY `staff_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `commission_type` enum('percentage','fixed','tiered','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `fixed_amount` decimal(10,2) DEFAULT NULL,
  `tiered_structure` json DEFAULT NULL,
  `service_amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `tip_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bonus_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `penalty_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_earning` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','on_hold','disputed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `earned_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bonuses` json DEFAULT NULL,
  `penalties` json DEFAULT NULL,
  `quality_multiplier` decimal(3,2) NOT NULL DEFAULT '1.00',
  `approval_status` enum('pending','approved','rejected','needs_review') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `calculation_details` json DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `period_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_commissions_booking_id_foreign` (`booking_id`),
  KEY `staff_commissions_service_id_foreign` (`service_id`),
  KEY `staff_commissions_approved_by_foreign` (`approved_by`),
  KEY `staff_commissions_staff_id_earned_date_index` (`staff_id`,`earned_date`),
  KEY `staff_commissions_branch_id_earned_date_index` (`branch_id`,`earned_date`),
  KEY `staff_commissions_payment_status_earned_date_index` (`payment_status`,`earned_date`),
  KEY `staff_commissions_approval_status_index` (`approval_status`),
  CONSTRAINT `staff_commissions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_commissions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_commissions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_commissions_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_commissions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_performance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `performance_date` date NOT NULL,
  `total_services` int NOT NULL DEFAULT '0',
  `completed_services` int NOT NULL DEFAULT '0',
  `cancelled_services` int NOT NULL DEFAULT '0',
  `total_revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `average_service_time` decimal(8,2) DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `total_reviews` int NOT NULL DEFAULT '0',
  `positive_reviews` int NOT NULL DEFAULT '0',
  `negative_reviews` int NOT NULL DEFAULT '0',
  `actual_start_time` time DEFAULT NULL,
  `scheduled_start_time` time DEFAULT NULL,
  `actual_end_time` time DEFAULT NULL,
  `scheduled_end_time` time DEFAULT NULL,
  `late_minutes` int NOT NULL DEFAULT '0',
  `present` tinyint(1) NOT NULL DEFAULT '1',
  `upsells_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `upsells_count` int NOT NULL DEFAULT '0',
  `tips_received` decimal(10,2) NOT NULL DEFAULT '0.00',
  `setup_time_minutes` int NOT NULL DEFAULT '0',
  `cleanup_time_minutes` int NOT NULL DEFAULT '0',
  `client_satisfaction_score` decimal(3,2) DEFAULT NULL,
  `revenue_target` decimal(10,2) DEFAULT NULL,
  `service_target` int DEFAULT NULL,
  `rating_target` decimal(3,2) DEFAULT NULL,
  `notes` json DEFAULT NULL,
  `achievements` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_performance_staff_id_performance_date_unique` (`staff_id`,`performance_date`),
  KEY `staff_performance_staff_id_performance_date_index` (`staff_id`,`performance_date`),
  KEY `staff_performance_branch_id_performance_date_index` (`branch_id`,`performance_date`),
  CONSTRAINT `staff_performance_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_performance_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_schedules_staff_id_branch_id_date_unique` (`staff_id`,`branch_id`,`date`),
  KEY `staff_schedules_staff_id_date_index` (`staff_id`,`date`),
  KEY `staff_schedules_branch_id_date_index` (`branch_id`,`date`),
  KEY `staff_schedules_is_available_index` (`is_available`),
  CONSTRAINT `staff_schedules_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_schedules_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `proficiency_level` enum('beginner','intermediate','expert','master') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_services_staff_id_service_id_unique` (`staff_id`,`service_id`),
  KEY `staff_services_service_id_foreign` (`service_id`),
  KEY `staff_services_proficiency_level_index` (`proficiency_level`),
  CONSTRAINT `staff_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_services_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferences` json DEFAULT NULL,
  `marketing_consent` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_account_status` enum('accepted','active','no_creation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_creation',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_user_type_branch_id_index` (`user_type`,`branch_id`),
  KEY `users_user_type_index` (`user_type`),
  KEY `users_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `waitlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `waitlists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `preferred_start_time` time DEFAULT NULL,
  `preferred_end_time` time DEFAULT NULL,
  `alternative_dates` json DEFAULT NULL,
  `alternative_staff` json DEFAULT NULL,
  `status` enum('pending','notified','declined','converted','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority_score` int NOT NULL DEFAULT '0',
  `priority_level` enum('low','medium','high','vip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `notification_method` enum('sms','email','whatsapp','call') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `auto_book` tinyint(1) NOT NULL DEFAULT '0',
  `max_wait_hours` int NOT NULL DEFAULT '72',
  `notified_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `response` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `discount_offered` decimal(8,2) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `waitlists_client_id_foreign` (`client_id`),
  KEY `waitlists_service_id_foreign` (`service_id`),
  KEY `waitlists_staff_id_foreign` (`staff_id`),
  KEY `waitlists_branch_id_status_index` (`branch_id`,`status`),
  KEY `waitlists_preferred_date_status_index` (`preferred_date`,`status`),
  KEY `waitlists_priority_score_created_at_index` (`priority_score`,`created_at`),
  KEY `waitlists_expires_at_index` (`expires_at`),
  CONSTRAINT `waitlists_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `waitlists_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `waitlists_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `waitlists_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_07_11_175445_create_service_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_07_11_175452_create_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_07_11_175458_create_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_07_11_175504_create_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_07_11_175510_create_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_07_11_175517_create_branch_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_07_11_175518_create_branch_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_07_11_175518_create_staff_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_07_11_175519_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_07_11_175519_create_staff_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_07_13_170547_update_users_table_for_branch_managers_final',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_07_16_123205_fix_payment_enums_comprehensive',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_07_18_191102_add_color_to_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_07_22_000001_create_waitlists_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_07_22_204431_create_staff_performance_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_07_22_204457_create_staff_commissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_07_22_205016_create_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_07_23_025313_create_analytics_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_07_23_035655_update_payment_status_enum_in_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_07_23_052028_add_name_column_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_07_23_052348_create_notifications_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_07_23_100637_update_users_table_fix_user_type_and_phone',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_07_23_202644_add_branch_id_to_payments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_07_23_202748_make_branch_id_non_nullable_in_payments_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_07_23_120000_create_expenses_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_07_23_120001_create_inventory_items_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_07_23_120002_create_inventory_transactions_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_07_23_140000_create_pos_transactions_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_07_23_140001_create_pos_transaction_items_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_07_23_140002_create_pos_receipts_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_07_23_140003_create_pos_daily_summaries_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_07_24_051627_create_gift_vouchers_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_07_24_051727_create_discount_coupons_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_07_24_051826_create_coupon_usages_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_07_24_051905_create_pos_payment_splits_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_07_24_051946_create_loyalty_points_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_07_24_052117_add_phase_two_fields_to_pos_transactions_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_07_24_052148_create_pos_promotions_table',10);
