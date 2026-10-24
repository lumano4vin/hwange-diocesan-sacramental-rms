<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Standard Diocesan Financial Categories (23-Line Double-Entry Cashbook)
 * Derived from Official Chancery / Mission Administration Format
 */

function get_diocesan_income_categories() {
    return [
        1 => ['code' => 'diocese', 'name' => 'Diocese', 'is_assessable' => false],
        2 => ['code' => 'bank_interest', 'name' => 'Bank interest', 'is_assessable' => false],
        3 => ['code' => 'pledges', 'name' => 'Pledges (isondlo)', 'is_assessable' => true],
        4 => ['code' => 'collection', 'name' => 'Collection (umnikelo)', 'is_assessable' => true],
        5 => ['code' => 'donation_abroad', 'name' => 'Donation from abroad', 'is_assessable' => false],
        6 => ['code' => 'local_donation', 'name' => 'Local Donation', 'is_assessable' => false],
        7 => ['code' => 'farm_garden_inc', 'name' => 'Farm/Garden', 'is_assessable' => false],
        8 => ['code' => 'income_gen_proj_inc', 'name' => 'Income Gen. Project', 'is_assessable' => false],
        9 => ['code' => 'catechists_fund', 'name' => 'Catechists fund', 'is_assessable' => false],
        10 => ['code' => 'loan_taken', 'name' => 'Loan taken', 'is_assessable' => false],
        11 => ['code' => 'loan_returned', 'name' => 'Loan returned', 'is_assessable' => false],
        12 => ['code' => 'refunds', 'name' => 'Refunds (electricity etc.)', 'is_assessable' => false],
        13 => ['code' => 'religious_congregation', 'name' => 'Religious Congregation', 'is_assessable' => false],
        14 => ['code' => 'sale_of_goods', 'name' => 'Sale of Goods (Repository)', 'is_assessable' => false],
        15 => ['code' => 'rent', 'name' => 'Rent', 'is_assessable' => false],
        16 => ['code' => 'fuel_from_communities', 'name' => 'Fuel from Communities', 'is_assessable' => false],
        17 => ['code' => 'holy_land_inc', 'name' => 'Holy Land', 'is_assessable' => false],
        18 => ['code' => 'charity', 'name' => 'Charity', 'is_assessable' => false],
        19 => ['code' => 'special_collection', 'name' => 'Special Collection', 'is_assessable' => false],
        20 => ['code' => 'lenten_collection', 'name' => 'Lenten Collection', 'is_assessable' => false],
        21 => ['code' => 'special_project', 'name' => 'Special Project', 'is_assessable' => false],
        22 => ['code' => 'peters_penny', 'name' => "Peter's Penny", 'is_assessable' => false],
        23 => ['code' => 'marian_shrine', 'name' => 'Marian Shrine', 'is_assessable' => false],
    ];
}

function get_diocesan_expenditure_categories() {
    return [
        1 => ['code' => 'bank_charges', 'name' => 'Bank charges', 'is_auto_levy' => false],
        2 => ['code' => 'building_repairs', 'name' => 'Building (repairs)', 'is_auto_levy' => false],
        3 => ['code' => 'construction', 'name' => 'Construction', 'is_auto_levy' => false],
        4 => ['code' => 'diocesan_levy', 'name' => 'Diocesan Levy (10%)', 'is_auto_levy' => true],
        5 => ['code' => 'catechists_exp', 'name' => 'Catechists', 'is_auto_levy' => false],
        6 => ['code' => 'farm_garden_exp', 'name' => 'Farm/Garden', 'is_auto_levy' => false],
        7 => ['code' => 'fuel_travel', 'name' => 'Fuel/travel', 'is_auto_levy' => false],
        8 => ['code' => 'toll_fee', 'name' => 'Toll Fee', 'is_auto_levy' => false],
        9 => ['code' => 'general_maintenance', 'name' => 'General Maintenance', 'is_auto_levy' => false],
        10 => ['code' => 'household_needs', 'name' => 'Household Needs', 'is_auto_levy' => false],
        11 => ['code' => 'income_gen_proj_exp', 'name' => 'Income Generating Projects', 'is_auto_levy' => false],
        12 => ['code' => 'staff_food_health', 'name' => 'Staff (Food/Health)', 'is_auto_levy' => false],
        13 => ['code' => 'nssa', 'name' => 'NSSA', 'is_auto_levy' => false],
        14 => ['code' => 'pastoral_work', 'name' => 'Pastoral work: Church Expenses', 'is_auto_levy' => false],
        15 => ['code' => 'phone_postage', 'name' => 'Phone and Postage', 'is_auto_levy' => false],
        16 => ['code' => 'priests_allowance', 'name' => 'Priests Allowance', 'is_auto_levy' => false],
        17 => ['code' => 'salaries_wages', 'name' => 'Salaries and Wages', 'is_auto_levy' => false],
        18 => ['code' => 'holy_land_exp', 'name' => 'Holy Land', 'is_auto_levy' => false],
        19 => ['code' => 'mca', 'name' => 'MCA', 'is_auto_levy' => false],
        20 => ['code' => 'repository_exp', 'name' => 'Repository', 'is_auto_levy' => false],
        21 => ['code' => 'parish_meetings', 'name' => 'Parish Meetings', 'is_auto_levy' => false],
        22 => ['code' => 'internet_wifi', 'name' => 'Internet/Wifi', 'is_auto_levy' => false],
        23 => ['code' => 'vehicle_service_repair', 'name' => 'Vehicle Service or Repair', 'is_auto_levy' => false],
    ];
}
