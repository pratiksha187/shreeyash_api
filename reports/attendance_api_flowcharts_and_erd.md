# Attendance API / Admin ERP Flowcharts and ERD

Generated from the local Laravel project in `C:\xampp\htdocs\attendance_api`.

This file uses Mermaid diagrams. You can open it in any Mermaid-supported Markdown viewer, or paste each diagram into https://mermaid.live.

## 1. Simple ERP Module Flow

```mermaid
flowchart TD
    A[Admin / Company Login] --> B[Dashboard]
    B --> C[HR]
    B --> D[Masters]
    B --> E[Site Work]
    B --> F[Purchase]
    B --> G[Stock]
    B --> H[Fleet]
    B --> I[Quality]

    C --> C1[Employees]
    C --> C2[Employee Attendance]
    C --> C3[Leave Requests]
    C --> C4[Missed Requests]
    C --> C5[Payments / Salary Slips]
    C --> C6[Driver Attendance]

    D --> D1[Site Master]
    D --> D2[Contractor Master]
    D --> D3[Labour Master]

    E --> E1[DPR Reports]
    E --> E2[Complaints]
    E --> E3[Challans]

    F --> F1[Diesel Purchase]
    F --> F2[Product Purchase]
    F --> F3[Challans]

    G --> G1[Material Master]
    G --> G2[Stock List]
    G --> G3[Material Requests]
    G --> G4[Material Issues]
    G --> G5[Stock Movement History]

    H --> H1[Vehicles]
    H --> H2[Vehicle Daily Sheet]
    H --> H3[Vehicle Drivers]
    H --> H4[Machinery Diesel]

    I --> I1[FDD Test Records]
    I --> I2[MIR File Reports]
```

## 2. Company / Tenant Database Flow

```mermaid
flowchart TD
    A[Super Admin Creates Company] --> B[Company Record]
    B --> C{Separate Database Needed?}
    C -->|Yes| D[Create Tenant Database]
    D --> E[Run Migrations on Tenant DB]
    E --> F[Sync Company, Plans, Subscription, Admin Users]
    C -->|Already Created| F
    F --> G[Company Admin Login]
    G --> H[Set Current Company / Tenant]
    H --> I[Company Data Reads and Writes in Tenant DB]
    H --> J[Central DB Keeps Company and Subscription Control]
```

## 3. Employee Attendance and Salary Flow

```mermaid
flowchart TD
    A[Employee Login in App/API] --> B[Clock In]
    B --> C[Attendance Record Created]
    C --> D[Clock Out]
    D --> E[Attendance Report]
    C --> F{Problem / Missed Entry?}
    F -->|Yes| G[Missed Attendance Request]
    G --> H[Admin Approves or Rejects]
    H --> E
    C --> I{Leave Needed?}
    I -->|Yes| J[Leave Request]
    J --> K[Admin Approval]
    K --> E
    E --> L[Payment Generation]
    L --> M[Salary Slip PDF]
```

## 4. Labour, Site Work, and DPR Flow

```mermaid
flowchart TD
    A[Create Site Master] --> B[Create Contractor]
    B --> C[Create Labour]
    C --> D[Engineer Adds Labour Attendance]
    D --> E[Admin Labour Attendance Report]

    A --> F[Engineer Creates DPR]
    F --> G[Hourly Work Rows]
    G --> H[Upload DPR Photos]
    H --> I[Admin Reviews DPR]

    A --> J[Complaints]
    A --> K[Challans]
```

## 5. Purchase, Stock, and Material Flow

```mermaid
flowchart TD
    A[Material Master] --> B[Material Stock]
    C[Product Purchase] --> D{Linked Material?}
    D -->|Yes| B
    D -->|No| E[Purchase Record Only]

    F[Material Request from Site/API] --> G[Admin Review]
    G --> H{Approved?}
    H -->|Yes| I[Issue Material]
    I --> J[Reduce Stock]
    J --> K[Stock Movement Ledger]
    H -->|Purchase Required| C
    H -->|Rejected| L[Request Closed]

    M[Daily Diesel Purchase] --> N[Site-wise Diesel Balance]
    N --> O[Opening + Supply - Used = Balance]
```

## 6. Fleet, Vehicle Sheet, Driver Attendance, and Machinery Diesel Flow

```mermaid
flowchart TD
    A[Vehicle Master] --> B[Vehicle Daily / Monthly Sheet]
    B --> C{Vehicle Type}
    C -->|Camper| D[Start Reading / End Reading / Total KM]
    C -->|Other Vehicle| E[First Half and Second Half Time]
    D --> F[Billing Calculation]
    E --> F

    A --> G[Vehicle Driver Master]
    G --> H[Driver Attendance]
    H --> I[Driver Attendance Export]

    A --> J[Machinery Diesel Sheet]
    J --> K[Issue Today Calculation]
    K --> L[Expected Consumption]
    L --> M[Evening Physical Balance]
    M --> N[Difference]
    N --> O[Auto Remarks: Extra Diesel Remaining / Diesel Missing / OK]
```

## 7. Main Model ERD

```mermaid
erDiagram
    COMPANY ||--o{ USER : has
    COMPANY ||--o{ COMPANY_SUBSCRIPTION : has
    SUBSCRIPTION_PLAN ||--o{ COMPANY_SUBSCRIPTION : used_by

    USER ||--o{ ATTENDANCE : marks
    USER ||--o{ PAYMENT : receives
    USER ||--o{ MISSED_ATTENDANCE_REQUEST : raises
    USER ||--o{ DAILY_PROGRESS_REPORT : creates
    USER ||--o{ COMPLAINT : raises
    USER ||--o{ CHALLAN : creates

    LABOUR_SITE ||--o{ CONTRACTOR : has
    CONTRACTOR ||--o{ LABOUR : has
    LABOUR_SITE ||--o{ LABOUR_ATTENDANCE : used_in
    CONTRACTOR ||--o{ LABOUR_ATTENDANCE : used_in
    LABOUR ||--o{ LABOUR_ATTENDANCE : marked_for
    USER ||--o{ LABOUR_ATTENDANCE : engineer_submits

    DAILY_PROGRESS_REPORT ||--o{ DAILY_PROGRESS_REPORT_HOUR : has
    DAILY_PROGRESS_REPORT_HOUR ||--o{ DAILY_PROGRESS_REPORT_PHOTO : has

    FDD_ROAD_SECTION ||--o{ FDD_TEST_RECORD : has

    VEHICLE ||--o{ VEHICLE_LOG : has
    VEHICLE ||--o{ VEHICLE_DRIVER : has
    VEHICLE ||--o{ VEHICLE_DRIVER_ATTENDANCE : has
    VEHICLE_DRIVER ||--o{ VEHICLE_DRIVER_ATTENDANCE : marked_for

    DAILY_DIESEL_PURCHASE ||--o{ DAILY_DIESEL_PURCHASE_SITE_ENTRY : has
    LABOUR_SITE ||--o{ DAILY_DIESEL_PURCHASE_SITE_ENTRY : receives
    LABOUR_SITE ||--o{ MACHINERY_DIESEL_LOG : used_in
    USER ||--o{ MACHINERY_DIESEL_LOG : engineer_submits

    MATERIAL ||--o{ MATERIAL_STOCK : has
    LABOUR_SITE ||--o{ MATERIAL_STOCK : stores_at
    MATERIAL ||--o{ MATERIAL_REQUEST : requested
    USER ||--o{ MATERIAL_REQUEST : requests
    LABOUR_SITE ||--o{ MATERIAL_REQUEST : needed_at
    MATERIAL_REQUEST ||--o{ MATERIAL_ISSUE : issues
    MATERIAL ||--o{ MATERIAL_ISSUE : issued
    MATERIAL ||--o{ STOCK_MOVEMENT : moves
    LABOUR_SITE ||--o{ STOCK_MOVEMENT : site

    MATERIAL ||--o{ PRODUCT_PURCHASE : linked_to
    LABOUR_SITE ||--o{ PRODUCT_PURCHASE : purchased_for
```

## 8. Non-Technical Reading Guide

- Company is the customer/business using this system.
- User is any person who logs in, such as employee, engineer, or admin.
- Site, contractor, and labour are the field-work master records.
- Attendance and labour attendance are separate because employees and contractor labour are tracked differently.
- DPR means Daily Progress Report, used for site work photos and hourly progress.
- Vehicle and vehicle log track vehicle usage, KM, diesel and billing.
- Vehicle driver attendance is for drivers assigned to vehicles.
- Diesel purchase tracks diesel bought and site-wise diesel balance.
- Machinery diesel tracks diesel issued to machinery and automatically shows missing/extra diesel.
- Product purchase and material stock connect purchases to available stock.
- Stock movement is the audit/history of every stock increase or decrease.
