<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Car Rental Management System API",
 *     version="1.0.0",
 *     description="A comprehensive car rental management system with user authentication, vehicle management, booking system, payment processing, and review functionality. This system supports both car rentals and property (home) rentals.",
 *     @OA\Contact(
 *         email="support@carrentalsystem.com",
 *         name="Car Rental System Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Car Rental API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="User Management",
 *     description="User profile and account management"
 * )
 *
 * @OA\Tag(
 *     name="Car Management",
 *     description="Vehicle listing, management, and search functionality"
 * )
 *
 * @OA\Tag(
 *     name="Home Management",
 *     description="Property rental listing and management"
 * )
 *
 * @OA\Tag(
 *     name="Booking Management",
 *     description="Booking creation, management, and tracking"
 * )
 *
 * @OA\Tag(
 *     name="Payment Processing",
 *     description="Payment methods and transaction handling"
 * )
 *
 * @OA\Tag(
 *     name="Reviews & Ratings",
 *     description="User reviews and rating system"
 * )
 *
 * @OA\Tag(
 *     name="Admin Functions",
 *     description="Administrative functions and controls"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="User notification management"
 * )
 *
 * @OA\Tag(
 *     name="Verification",
 *     description="User and document verification processes"
 * )
 *
 * @OA\Tag(
 *     name="Content Management",
 *     description="Landing page content, FAQs, and general information"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "first_name", "last_name", "email", "phone"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="middle_name", type="string", example="A."),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="0912345678"),
 *     @OA\Property(property="address", type="string", example="123 Main Street"),
 *     @OA\Property(property="city", type="string", example="Addis Ababa"),
 *     @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="role", type="string", enum={"User", "Admin", "SuperAdmin"}, example="User"),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Active", "Inactive", "Banned"}, example="Active"),
 *     @OA\Property(property="is_verified", type="boolean", example=true),
 *     @OA\Property(property="two_factor_enabled", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Car",
 *     type="object",
 *     required={"id", "make", "model", "price_per_day", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="owner_id", type="integer", example=1),
 *     @OA\Property(property="make", type="string", example="Toyota"),
 *     @OA\Property(property="model", type="string", example="Camry"),
 *     @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186"),
 *     @OA\Property(property="seating_capacity", type="integer", example=5),
 *     @OA\Property(property="license_plate", type="string", example="AB123CD"),
 *     @OA\Property(property="status", type="string", enum={"Available", "Rented", "Maintenance", "Blocked"}, example="Available"),
 *     @OA\Property(property="price_per_day", type="number", format="float", example=50.00),
 *     @OA\Property(property="fuel_type", type="string", enum={"Gasoline", "Diesel", "Electric", "Hybrid"}, example="Gasoline"),
 *     @OA\Property(property="transmission", type="string", enum={"Manual", "Automatic"}, example="Automatic"),
 *     @OA\Property(property="location_lat", type="number", format="float", example=9.0320),
 *     @OA\Property(property="location_long", type="number", format="float", example=38.7615),
 *     @OA\Property(property="pickup_location", type="string", example="Addis Ababa Airport"),
 *     @OA\Property(property="return_location", type="string", example="Addis Ababa Airport"),
 *     @OA\Property(property="listing_type", type="string", enum={"rent", "sell", "both"}, example="rent"),
 *     @OA\Property(property="sell_price", type="number", format="float", example=25000.00),
 *     @OA\Property(property="is_negotiable", type="boolean", example=true),
 *     @OA\Property(property="mileage", type="integer", example=50000),
 *     @OA\Property(property="year", type="integer", example=2020),
 *     @OA\Property(property="condition", type="string", enum={"New", "Excellent", "Good", "Fair", "Poor"}, example="Good"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     required={"id", "user_id", "car_id", "start_date", "end_date", "total_price", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="car_id", type="integer", example=1),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-01-20"),
 *     @OA\Property(property="total_price", type="number", format="float", example=250.00),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Confirmed", "Active", "Completed", "Cancelled"}, example="Confirmed"),
 *     @OA\Property(property="pickup_location", type="string", example="Addis Ababa Airport"),
 *     @OA\Property(property="return_location", type="string", example="Addis Ababa Airport"),
 *     @OA\Property(property="special_requests", type="string", example="Child seat required"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Home",
 *     type="object",
 *     required={"id", "title", "price", "listing_type"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="owner_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Beautiful 2-Bedroom Apartment"),
 *     @OA\Property(property="description", type="string", example="Modern apartment with great city views"),
 *     @OA\Property(property="location", type="string", example="Bole, Addis Ababa"),
 *     @OA\Property(property="price", type="number", format="float", example=80.00),
 *     @OA\Property(property="listing_type", type="string", enum={"rent", "sell", "both"}, example="rent"),
 *     @OA\Property(property="bedrooms", type="integer", example=2),
 *     @OA\Property(property="bathrooms", type="integer", example=2),
 *     @OA\Property(property="area", type="number", format="float", example=120.5),
 *     @OA\Property(property="amenities", type="array", @OA\Items(type="string"), example={"WiFi", "Parking", "Pool"}),
 *     @OA\Property(property="status", type="string", enum={"Available", "Rented", "Sold", "Blocked"}, example="Available"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     required={"id", "user_id", "rating", "comment"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="car_id", type="integer", example=1),
 *     @OA\Property(property="home_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
 *     @OA\Property(property="comment", type="string", example="Great car, very comfortable and clean!"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     required={"id", "user_id", "amount", "status", "payment_method"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="booking_id", type="integer", example=1),
 *     @OA\Property(property="home_booking_id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=250.00),
 *     @OA\Property(property="currency", type="string", example="ETB"),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Completed", "Failed", "Refunded"}, example="Completed"),
 *     @OA\Property(property="payment_method", type="string", enum={"Chapa", "Bank Transfer", "Cash"}, example="Chapa"),
 *     @OA\Property(property="tx_ref", type="string", example="tx_12345"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     required={"id", "user_id", "title", "message", "type"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Booking Confirmed"),
 *     @OA\Property(property="message", type="string", example="Your booking has been confirmed"),
 *     @OA\Property(property="type", type="string", enum={"info", "success", "warning", "error"}, example="success"),
 *     @OA\Property(property="read", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(property="errors", type="object", example={"field": {"The field is required."}}),
 *     @OA\Property(property="code", type="integer", example=400)
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *     @OA\Property(property="data", type="object", example={}),
 *     @OA\Property(property="status", type="string", example="success")
 * )
 */
class SwaggerController extends Controller
{
    // This class is only for Swagger documentation purposes
}
