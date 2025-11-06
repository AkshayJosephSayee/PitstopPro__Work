

CREATE TABLE `tbl_admins` (
  `admin_id` int(11) NOT NULL,
  `Username` varchar(60) NOT NULL UNIQUE,
  `password` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `tbl_bill`
--

CREATE TABLE `tbl_bill` (
  `bill_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `Payment_status` enum('paid','not paid') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bookings`
--

CREATE TABLE `tbl_bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` VARCHAR(100) DEFAULT NULL,
  `customer_email` VARCHAR(100) DEFAULT NULL,
  `customer_mobile` VARCHAR(20) DEFAULT NULL,
  `service_type` VARCHAR(100) DEFAULT NULL,
  `User_id_` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `Service_id` int(11) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_time` time DEFAULT NULL,
  `b_status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `special_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `User_id_` int(11) NOT NULL,
  `Username` varchar(60) NOT NULL,
  `email` varchar(60) NOT NULL,
  `Phone` varchar(10) NOT NULL,
  `Password` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_feedback`
--

CREATE TABLE `tbl_feedback` (
  `feedback_id` int(11) NOT NULL,
  `User_id_` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `REVIEW` text DEFAULT NULL,
  `Submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_services`
--

CREATE TABLE `tbl_services` (
  `Service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `Estimated_duration` int(11) NOT NULL,
  PRIMARY KEY (`Service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_slot`
--

CREATE TABLE `tbl_slot` (
  `slot_id` int(11) NOT NULL,
  `slot_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `S_status` enum('Available','Not Available') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_vehicles`
--

CREATE TABLE `tbl_vehicles` (
  `vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
  `User_id_` int(11) DEFAULT NULL,
  `registration_number` varchar(20) DEFAULT NULL UNIQUE,
  `vehicle_company` varchar(20) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
-- Removed - no longer needed

--
-- Indexes for table `tbl_admins`
--
ALTER TABLE `tbl_admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `tbl_bill`
--
ALTER TABLE `tbl_bill`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `fk_tbl_bill_user` (`booking_id`);

--
-- Indexes for table `tbl_bookings`
--
ALTER TABLE `tbl_bookings`
  ADD KEY `fk_bookings_user` (`User_id_`),
  ADD KEY `fk_bookings_vehicle` (`vehicle_id`),
  ADD KEY `fk_bookings_service` (`Service_id`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`User_id_`);

--
-- Indexes for table `tbl_feedback`
--
ALTER TABLE `tbl_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `User_id_` (`User_id_`);

--
-- Indexes for table `tbl_services`
--
-- PRIMARY KEY already defined in CREATE TABLE

--
-- Indexes for table `tbl_slot`
--
ALTER TABLE `tbl_slot`
  ADD PRIMARY KEY (`slot_id`);

--
-- Indexes for table `tbl_vehicles`
--
ALTER TABLE `tbl_vehicles`
  ADD KEY `fk_tbl_vehicles_user` (`User_id_`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_admins`
--
ALTER TABLE `tbl_admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_bill`
--
ALTER TABLE `tbl_bill`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `User_id_` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_feedback`
--
ALTER TABLE `tbl_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_slot`
--
ALTER TABLE `tbl_slot`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_bill`
--
ALTER TABLE `tbl_bill`
  ADD CONSTRAINT `fk_tbl_bill_user` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`booking_id`);

--
-- Constraints for table `tbl_bookings`
--
ALTER TABLE `tbl_bookings`
  ADD CONSTRAINT `fk_bookings_service` FOREIGN KEY (`Service_id`) REFERENCES `tbl_services` (`Service_id`),
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`User_id_`) REFERENCES `tbl_customer` (`User_id_`),
  ADD CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `tbl_vehicles` (`vehicle_id`);

--
-- Constraints for table `tbl_feedback`
--
ALTER TABLE `tbl_feedback`
  ADD CONSTRAINT `tbl_feedback_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`booking_id`),
  ADD CONSTRAINT `tbl_feedback_ibfk_2` FOREIGN KEY (`User_id_`) REFERENCES `tbl_customer` (`User_id_`);

--
-- Constraints for table `tbl_vehicles`
--
ALTER TABLE `tbl_vehicles`
  ADD CONSTRAINT `fk_tbl_vehicles_user` FOREIGN KEY (`User_id_`) REFERENCES `tbl_customer` (`User_id_`);
COMMIT;

