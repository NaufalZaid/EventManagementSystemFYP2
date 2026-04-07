# EventManagementSystemFYP2

# Project Title
Event Management System with Automated Scheduling Optimization Using Genetic Algorithms

# Description
An Event Management System that utilizes genetic algorithms for event scheduling optimization.

# Tech Stack
* Backend: Java
* Frontend: React
* Database: MongoDB
# Modules
* Authetication
* Event Management
* Registration
* Venue Management
* Attendance Tracking
* Notifications
* Scheduling
* Analytics
# Database Draft

## Users
* Purpose: store all system users and roles
* Key fields: _id, name, email, passwordHash, role
## Venues
* Purpose: store venue information and capacity
* Key fields: _id, name, location, capacity, blackoutDates

## Events
* Purpose: store all event details
* Key fields: _id, title, description, organizerId, venueId, startDateTime, endDateTime, status

## Registrations
* Purpose: store student registrations for events
* Key fields: _id, eventId, userId, registeredAt, status

## Notifications
* Purpose: store notifications sent to users
* Key fields: _id, userId, eventId, type, message, isRead

## Attendance records
* Purpose: store attendance check-ins
* Key fields: _id, eventId, userId, checkedInAt, method

## Proposals
* Purpose: store organizer proposals and admin review
* Key fields: _id, organizerId, proposedVenueId, expectedAttendance, status, reviewRemarks

## Personal schedule items
* Purpose: store personal schedules to detect clashes
* Key fields: _id, userId, title, type, startDateTime, endDateTime
