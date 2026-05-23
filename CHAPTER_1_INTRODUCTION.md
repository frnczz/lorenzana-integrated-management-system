# CHAPTER 1: INTRODUCTION TO LORINIMS

## 1.1 System Overview and Context

The Lorenzana Food Corporation Integrated Management System (LORINIMS) is a locally hosted, web-based platform developed to centralize and integrate the core operational processes of Lorenzana Food Corporation into a single, coordinated information system. The system is designed to support real-time data sharing across departments, reduce manual record handling, and improve operational accuracy, traceability, and decision-making. LORINIMS operates within a local server environment using PHP and MySQL, ensuring system availability even without continuous internet connectivity.

LORINIMS supports seven (7) primary user roles, each with role-based access control enforced at the server level. These users include Administrator, Sales Staff, Production Personnel, Warehouse Staff, Procurement Officers, Quality Control Inspectors, and Accounting Staff. Each role is granted access only to the modules and functions relevant to their responsibilities, ensuring data security, accountability, and proper workflow separation. User authentication, profile management, and permission enforcement are implemented across all system modules.

The system is composed of multiple integrated functional modules. The Production Management Module enables the recording of production requests, batch creation, batch status updates, raw material consumption, fermentation tracking, and finished goods output. Production activities are tightly linked to inventory through an event-driven inventory update mechanism, ensuring that raw material deductions and finished goods increments are consistently synchronized. The Inventory Management Module maintains real-time records of raw materials and finished products, tracks stock movements, supports stock reservations for sales orders, and manages expiry-related releases through scheduled background processes.

Procurement operations are handled through the Procurement and Supplier Management Module, which supports purchase requisitions, purchase orders, supplier deliveries, goods receiving reports (GRN), supplier invoices, returns, and payment processing. Once materials are received and approved, inventory records are automatically updated. Quality assurance is supported by the Quality Control Module, which records inspection results for both raw materials and production batches, enforces approval workflows, and links QC results to specific materials or batch numbers to ensure traceability and compliance.

Customer transactions are processed through the Sales and Distribution Module, which manages customer records, sales orders, order items, stock reservations, delivery scheduling, invoicing, and payment recording. A GPS-based Delivery Tracking feature allows delivery personnel to update real-time location data and upload proof of delivery, improving visibility and accountability during product distribution. All sales and delivery transactions are synchronized with accounting records to ensure financial accuracy.

Financial operations are consolidated within the Accounting and Expense Monitoring Module, which records invoices, customer payments, supplier payments, operational expenses, and financial approvals. The system enforces data integrity through foreign key constraints and approval-based actions, preventing unauthorized deletion or modification of critical financial records. Employee-related processes are managed through the Payroll and Employee Management Module, which records employee profiles, attendance logs, payroll rates, deductions, and automated payroll processing based on recorded attendance data.

LORINIMS also includes supporting system features such as an API layer for asynchronous operations, PDF generation for invoices and reports, scheduled background tasks (cron jobs) for inventory releases and system event processing, database migration tools, and role-specific dashboards for sales, production, warehouse, procurement, QC, accounting, and administration. Reporting modules provide summaries such as remittances and statutory computations. Collectively, these components form a scalable, maintainable, and fully integrated management system that consolidates production, inventory, procurement, quality control, sales, accounting, payroll, and logistics into a single digital platform tailored to the operational needs of Lorenzana Food Corporation.

---

## 1.2 Terminology and Key Definitions

The following terminology are described both conceptually and functionally to help readers understand the project's content and technical components.

### 1.2.1 Technology and Infrastructure Terms

**AJAX** - Asynchronous JavaScript and XML, a technique used for asynchronous web requests without reloading the page.

**Apache** - A widely-used open-source web server software that serves web pages to users.

**Backend** - The part of the system that users do not see. It handles data processing, database operations, and business logic.

**Bootstrap** - A free and open-source CSS framework for responsive, mobile-first web design.

**Frontend** - The visible part of the system that users interact with, including design elements, forms, buttons, and navigation tools.

**Geolocation API** - A web API that allows websites to access the user's geographical position through the browser.

**Integrated Development Environment (IDE)** - A software application that provides a complete set of tools that allow developers to write, compile, debug, and test code within a single platform.

**jQuery** - A fast, small, and feature-rich JavaScript library that simplifies HTML document traversal and manipulation.

**JSON** - JavaScript Object Notation, a lightweight data-interchange format used for API communication.

**Leaflet.js** - An open-source JavaScript library for mobile-friendly interactive maps, used for GPS tracking in the system.

**Middleware** - Software that acts as a bridge between an operating system, database, or application components, especially in distributed systems.

**MySQL ORDER BY** - A SQL clause used to sort query results in ascending or descending order based on specified columns.

**OpenStreetMap** - A free, editable map of the world built by volunteers, used as the map tile source for GPS tracking.

**PHP sort()** - A built-in PHP function that sorts an array in ascending order using built-in algorithms.

**Processor** - The electrical component or digital circuit that performs operations on data from memory or other data sources.

**RAM (Random Access Memory)** - A type of short-term computer memory used to temporarily store data needed by applications and the operating system.

**Visual Studio Code** - A lightweight integrated development environment used to write, edit, debug, and manage software code.

**XAMPP** - A free and open-source cross-platform web server solution stack package containing Apache, MySQL, PHP, and Perl.

### 1.2.2 System and Interface Terms

**Admin Panel** - A part of the system interface that is accessible only to administrators, where they can manage users, inventory, transactions, and settings.

**Application Programming Interface (API)** - A set of rules and protocols that allows different software applications to communicate with each other by sending, receiving, and processing data.

**API Endpoint** - A specific URL where an API can be accessed to perform operations or retrieve data.

**Dashboard** - A central screen or panel where key data and system modules are accessible, such as sales summaries, inventory status, and operational metrics.

**Drop-down Menu** - A user interface element that displays a list of options when clicked, often used for selecting categories, roles, or transaction types.

**Editable Fields** - Sections in a form or system interface that allow users to enter, modify, or update information such as prices, quantities, or account details.

**Kitchen Display System (KDS)** - A digital screen placed in the kitchen area that shows real-time incoming orders. It helps kitchen staff track, prepare, and serve orders efficiently.

**User Interface (UI)** - The point of interaction between the user and the system. This includes screens, forms, buttons, icons, keyboards, and other interactive components.

### 1.2.3 Database and Data Management Terms

**CRUD** - Create, Read, Update, Delete - the four basic operations for persistent storage in database systems.

**Data Flow Diagram (DFD)** - A graphical representation used to show how data flows within a system, including sources, processes, storage, and outputs.

**Encapsulation** - A principle in object-oriented programming where related data and functions are grouped into a single unit or class, improving security and modularity.

**Foreign Key** - A field in one database table that uniquely identifies a row of another table, establishing relationships between tables.

**Session Management** - A mechanism for maintaining user state and authentication across multiple page requests.

**Sorting Algorithm** - A method used to arrange data in a particular order, such as ascending or descending, based on specified criteria.

---

## 1.3 Purpose and Description

### 1.3.1 Primary Purpose

The primary purpose of this study is to develop the Lorenzana Food Corporation Integrated Management System (LORINIMS), a comprehensive, web-based platform designed to streamline, automate, and unify the company's core operational processes. As Lorenzana Food Corporation continues to expand its domestic and international presence, its reliance on manual recording, spreadsheet-driven workflows, and department-specific tools creates bottlenecks that hinder productivity and accuracy. LORINIMS aims to resolve these challenges by providing a connected digital ecosystem where information flows seamlessly across production, inventory, procurement, quality assurance, distribution, logistics, and accounting units.

### 1.3.2 Production Management Module

One of the central components of the system is the Production Management Module, which digitizes and records fermentation batches, bottling outputs, packaging activities, raw-material consumption, and daily production targets. This module ensures that every batch of fish sauce, shrimp paste, or soy sauce is properly documented, traceable, and monitored for quality and compliance. By automating data logging, the system eliminates errors associated with manual entries and provides real-time visibility of production performance.

### 1.3.3 Inventory Management Module

The Inventory Management Module works in direct synchronization with production, warehouse, procurement, and sales operations. It automatically updates raw-material and finished-goods quantities after each production run, delivery dispatch, or incoming stock receipt. Real-time tracking of expiration dates, stock levels, and warehouse movements helps the company avoid shortages, minimize wastage, and maintain consistent supply for both local and export demands.

### 1.3.4 Procurement and Supplier Management Module

Supporting this is the Procurement and Supplier Management Module, which handles supplier profiles, purchase requests, approved orders, and delivery records. By centralizing purchasing data, the system ensures efficient coordination with vendors, prevents duplicate or unauthorized purchases, and maintains accurate records of material costs and supplier performance. Once materials arrive, their quantities are immediately reflected in the inventory system, creating a closed-loop workflow between procurement, warehouse, and production.

### 1.3.5 Quality Control Module

To uphold product quality and food safety standards, the Quality Control Module logs inspection findings, sample test results, sensory evaluations, moisture content readings, and non-conformance reports. By linking QC results directly to batch numbers, the system strengthens traceability and allows management to promptly identify and resolve quality issues. This is especially critical for export shipments that require strict compliance documentation.

### 1.3.6 Sales and Distribution Module

The Sales and Distribution Module manages client orders, shipment schedules, delivery assignments, and route planning. It automates the approval of customer orders, updates stock reservations, and generates digital delivery documents. A notable enhancement of LORINIMS is its integration with a GPS Delivery Tracking Module, complemented by a mobile application for truck drivers. Through the mobile app, drivers can share real-time GPS location, update delivery status (e.g., "On the Way," "Delivered"), and upload digital proof of delivery. This ensures transparency, reduces communication delays, and supports more accurate logistics monitoring.

### 1.3.7 Accounting and Expense Tracking Module

All financial transactions including expenses, supplier payments, fuel costs, production-related spending, and customer payments are organized through the Accounting and Expense Tracking Module. This component consolidates data from procurement, sales, and inventory activities, enabling automatic computation of revenues, costs, and profit margins. By eliminating manual consolidation of financial files, the module accelerates reporting and supports more reliable financial decision-making.

### 1.3.8 Analytics and Dashboard Module

To assist management in evaluating operational performance, the system includes an Analytics and Dashboard Module that visualizes key indicators such as monthly production output, raw-material consumption, cost analysis, sales summaries, order volumes, delivery accuracy, and quality control trends. These insights help managers identify production bottlenecks, forecast supply requirements, estimate profitability, and develop strategic solutions.

### 1.3.9 Digital Document Generation Module

Finally, the system features a Digital Document Generation Module, which automates the creation of purchase orders, batch reports, delivery receipts, sales invoices, and compliance records. This ensures that all documents are organized, consistent, and accessible, supporting transparency and audit readiness.

### 1.3.10 Technology Stack and Architecture

LORINIMS is developed using PHP for backend operations and system security, MySQL for managing relational data, and Bootstrap for a clean and user-friendly front-end interface. It incorporates a role-based access control system that enforces strict authorization across all user groups, including administrators, production supervisors, warehouse personnel, quality control staff, procurement officers, accounting personnel, and drivers. This integrated architecture ensures accurate data processing, minimizes manual workload, and promotes a more coordinated and efficient workflow across all departments of the company.

Through these interconnected modules, LORINIMS aims to enhance operational efficiency, strengthen product traceability, support data-driven management, and prepare Lorenzana Food Corporation for further growth in today's increasingly digital and competitive food manufacturing industry.

---

## 1.4 Objectives of the Study

### 1.4.1 General Objective

The general objective of this study is to develop an Integrated Management System for Lorenzana Food Corporation that centralizes core business operations, automates daily transactions, and provides accurate data for management decision-making.

### 1.4.2 Specific Objectives

1. To develop a role-based system that controls user access for administrators and key operational personnel.

2. To design modules for production, inventory, procurement, quality control, sales and delivery, and accounting to automate daily processes and minimize manual work.

3. To ensure real-time recording of production output, stock levels, sales transactions, and supplier deliveries to improve data accuracy and traceability.

4. To generate essential business documents such as batch reports, purchase orders, invoices, and delivery records automatically through the system.

5. To provide analytics and reporting tools that summarize production, inventory, expenses, and sales performance for management review.

6. To ensure system reliability, security, and usability through data protection measures, backup procedures, and an intuitive interface.

---

## 1.5 Significance of the Study

The significance of this study lies in demonstrating how an integrated production, inventory, procurement, quality control, and accounting system can greatly enhance operational efficiency, accuracy, and decision-making within a food manufacturing environment. By centralizing and automating processes that are traditionally manual, the system aims to reduce inconsistencies, strengthen product traceability, and support the company's long-term growth and competitiveness. This study highlights how digital transformation can streamline the workflow of a condiment manufacturing company from raw material acquisition to final product distribution, ultimately ensuring higher productivity, reliability, and operational sustainability.

### 1.5.1 Significance to Management

This study is significant to the management of Lorenzana Food Corporation because it provides real-time operational visibility through consolidated dashboards, production analytics, and financial summaries. With access to accurate and updated information, management can make data-driven decisions related to production scheduling, budgeting, procurement planning, and performance evaluation. The system also improves transparency and accountability across departments, enabling more efficient strategic and operational management.

### 1.5.2 Significance to Production and Quality Control Staff

For production personnel and quality control officers, the system simplifies daily reporting tasks by replacing manual logs with automated data entry. It ensures accurate recording of batch numbers, inspection results, and material usage. This reduces operational errors and strengthens product traceability, an essential component in meeting food safety standards and regulatory compliance. Staff can also monitor workflow more efficiently, allowing faster identification of production issues or quality deviations.

### 1.5.3 Significance to Accounting Department

The system benefits the accounting department by automating financial processes such as tracking expenses, supplier payments, sales records, and profit computation. This reduces the likelihood of miscalculations, speeds up financial reporting, and improves accuracy. The availability of organized records also supports smoother auditing, budgeting, and financial planning.

### 1.5.4 Significance to Warehouse and Inventory Personnel

Warehouse staff will benefit from real-time inventory updates, automated stock deductions, and alerts for low or critical stock levels. This reduces the risk of shortages or overstocking and ensures that production schedules can be followed without delays. Accurate records also improve accountability and enhance coordination between the warehouse, production, and procurement departments.

### 1.5.5 Significance to Suppliers and Distributors

Suppliers and distributors will experience improved transparency and coordination through digital purchase orders, delivery status tracking, and documented transaction histories. This system strengthens trust between partners, reduces miscommunication, and supports smoother procurement and distribution processes, especially for large-volume or export shipments.

### 1.5.6 Significance to Researchers and Future Research

The study provides insight into how integrated systems enhance production efficiency, inventory accuracy, quality compliance, and financial documentation in the food manufacturing sector. It helps researchers understand how digital systems address common operational challenges and how technological integration can transform traditional manufacturing workflows. This study serves as a valuable reference for future researchers seeking to develop or improve integrated information systems for manufacturing companies. It provides a framework for understanding system requirements, module interactions, and operational challenges in the condiment industry. Future researchers can use this foundation to explore enhancements, such as IoT-based monitoring, predictive analytics, and advanced automation.

---

## 1.6 Scope and Limitations of the Study

### 1.6.1 System Scope

This study focuses on the design and development of the Lorenzana Food Corporation Integrated Management System (LORINIMS), a web-based platform intended to streamline and automate key operational processes within a food manufacturing environment. The scope of the system includes the integration of multiple functional modules such as:

- Production Management
- Inventory Monitoring
- Procurement and Supplier Management
- Quality Control
- Sales and Distribution Tracking
- Accounting and Expense Monitoring
- Payroll and Employee Management
- Centralized Analytics Dashboard

These components were designed to improve data accuracy, reduce manual documentation, enhance traceability, and support data-driven decision-making across the organization.

### 1.6.2 Operational Scope

The system was developed specifically to address the existing workflow of Lorenzana Food Corporation, particularly its processes related to fermentation, bottling, packaging, material handling, supplier coordination, quality inspection, and delivery monitoring. LORINIMS is intended for use by on-site personnel including production staff, warehouse staff, accounting staff, quality control officers, delivery coordinators, and management, each granted access through predefined user roles. The system operates as a web-based application and requires an active internet connection for cloud synchronization and multi-user access.

### 1.6.3 Development Phase Scope

This study focuses on building a functional prototype aligned with the current operational needs of the company. It does not include mobile application development, though a mobile app for GPS-based delivery tracking is being considered for future extension. Similarly, although the system includes a Delivery Monitoring feature, real-time GPS hardware integration for delivery trucks is not yet part of the initial prototype and will be incorporated only in future development phases.

### 1.6.4 Study Limitations

Several limitations define the boundaries of this study:

**Hardware Integration Limitations:** Hardware integrations such as barcode scanners, automated weighing systems, RFID tagging, or Internet-of-Things (IoT) sensors are not included in the current system version.

**Payment and Billing Limitations:** Online payment gateways, digital billing solutions, automated expense auditing, and mobile wallet platforms are not implemented in this version.

**Maintenance and Deployment Limitations:** The system does not cover long-term maintenance, continuous improvement, or large-scale deployment beyond the prototype stage.

**Organizational Scope Limitations:** The system's applicability is limited to the organizational structure and workflow of Lorenzana Food Corporation. While the architecture is designed to be scalable, modification would be required before implementing the system in other food manufacturing companies with different processes, regulatory requirements, or production arrangements.

**Security Limitations:** Although basic security measures such as role-based authentication and controlled access are included, the system does not yet implement advanced cybersecurity features such as multi-factor authentication, encrypted database storage, intrusion detection systems, or automated security auditing. These enhancements are recommended for future versions to ensure stronger data protection.

---

## 1.7 Conceptual Framework

### 1.7.1 Framework Overview

The Conceptual Framework of this study is organized into three major components: Input, Process, and Output. This framework outlines how the researchers gathered requirements, developed the system, and produced the final Integrated Management System for Lorenzana Food Corporation.

### 1.7.2 Input Phase

The input phase involved collecting comprehensive information about the existing workflow and operational challenges of Lorenzana Food Corporation. This included reviewing current practices in:

- Production monitoring
- Raw-material handling
- Quality inspection
- Procurement activities
- Warehouse operations
- Sales distribution
- Financial documentation

The researchers also gathered insights through interviews and observations with production staff, warehouse personnel, accountants, quality control officers, and management to identify the company's pain points and priority needs.

In addition, the researchers identified technical requirements necessary for developing the system. These included:

- Web-based platform specifications
- Database structures for handling batch records and inventory logs
- GPS integration requirements for delivery tracking
- Analytics tools for real-time performance reporting
- Functional requirements based on existing best practices in food manufacturing systems and comparable enterprise resource management solutions

### 1.7.3 Process Phase

The process phase involved the system design, development, and refinement of the Lorenzana Food Corporation Integrated Management System (LORINIMS). The development team designed the architecture for major modules such as:

- Production Management
- Inventory Management
- Procurement and Supplier Handling
- Quality Control Monitoring
- Sales and Distribution Tracking
- Accounting and Expense Monitoring
- Analytics Dashboard
- Future mobile-based GPS tracking feature for delivery verification

A user-friendly interface was developed for desktop users, focusing on clarity, accessibility, and role-based navigation. Database structures were implemented to support real-time updates across production, inventory, quality control, and accounting records. 

Testing phases including:

- Initial prototype testing
- Module-level testing
- Usability evaluations with selected company staff

Feedback from testing was used to refine workflows, correct inconsistencies, improve system responsiveness, and ensure seamless communication among all modules.

### 1.7.4 Output Phase

The output phase resulted in a functional prototype of LORINIMS, an integrated management system tailored to the specific needs of Lorenzana Food Corporation. The system consolidates major operational processes into a centralized digital platform, enabling:

- Automated batch recording
- Real-time inventory updates
- Structured supplier management
- Quality control documentation
- Delivery tracking
- Financial recording
- Comprehensive analytics reporting

The final product enhances operational efficiency, reduces manual errors, promotes product traceability, improves coordination between departments, and supports data-driven decision-making for management. LORINIMS also establishes a scalable foundation for future enhancements, including mobile GPS tracking, barcode integration, and expanded automation features.

---

## 1.8 Document Organization

This thesis is organized as follows:

- **Chapter 1: Introduction** - Provides system overview, objectives, significance, scope, and conceptual framework
- **Chapter 2: System Design and Architecture** - Details the system modules, database design, and technical specifications
- **Chapter 3: Implementation and Development** - Documents the development process, tools used, and implementation details
- **Chapter 4: Testing and Results** - Presents testing methodologies, results, and system performance metrics
- **Chapter 5: Conclusion and Recommendations** - Summarizes findings and recommends future enhancements

---
