<?php

class BankHolidayService
{
    /**
     * @var int Any bank holiday defined in the database, not in this class
     */
    const SPECIAL_HOLIDAY = 9999999;
    const NO_HOLIDAY = 0;
    /**
     * @var int The first weekday in January
     */
    const NEW_YEARS_DAY = 10;
    /**
     * @var int The last Friday before Easter Sunday
     */
    const GOOD_FRIDAY = 20;
    /**
     * @var int The day after Easter Sunday
     */
    const EASTER_MONDAY = 30;
    /**
     * @var int The first Monday in May
     */
    const MAY_DAY = 40;
    /**
     * @var int The last Monday in May
     */
    const SPRING_BANK_HOLIDAY = 50;
    /** 
     * @var int The last Monday in August
     */
    const AUGUST_BANK_HOLIDAY = 60;
    /**
     * @var int The first weekday on or after 25 December
     */
    const CHRISTMAS_DAY = 70;
    /**
     * @var int The second weekday on or after 25 December
     */
    const BOXING_DAY = 80;
    
    private static $bhNames = array(
        self::NEW_YEARS_DAY => "New Year's Day",
        self::GOOD_FRIDAY => "Good Friday",
        self::EASTER_MONDAY => "Easter Monday",
        self::MAY_DAY => "May Day",
        self::SPRING_BANK_HOLIDAY => "Spring Bank Holiday",
        self::AUGUST_BANK_HOLIDAY => "August Bank Holiday",
        self::CHRISTMAS_DAY => "Christmas Day",
        self::BOXING_DAY => "Boxing Day"
    );
    
    /**
     * @var BankHolidayService
     */
    private static $instance;
    
    /**
     * @var string[][] Cached special bank holidays from the database
     *
     * Format: year => array(yyyy-mm-dd => Bank Holiday Name)
     */
    private $specialHolidays = array();
    
    private function __construct()
    {
        
    }
    
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BankHolidayService();
        }
        
        return self::$instance;
    }
    
    /**
     * Get the bank holiday code for a given date
     *
     * @param DateTime $date The date to search for // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     *
     * @return int Bank holiday code
     */
    public function getBankHoliday(DateTime $date)
    {
        $month = $date->format('n');
        $bankHoliday = self::NO_HOLIDAY;
        
        switch ($month) {
            case 1:
                if ($this->isNewYearsDay($date))
                    $bankHoliday = self::NEW_YEARS_DAY;
                break;
            case 3:
            case 4:
                if ($this->isGoodFriday($date))
                    $bankHoliday = self::GOOD_FRIDAY;
                elseif ($this->isEasterMonday($date))
                    $bankHoliday = self::EASTER_MONDAY;
                break;
            case 5:
                if ($this->isMayDay($date))
                    $bankHoliday = self::MAY_DAY;
                elseif ($this->isSpringBankHoliday($date))
                    $bankHoliday = self::SPRING_BANK_HOLIDAY;
                break;
            case 8:
                if ($this->isAugustBankHoliday($date))
                    $bankHoliday = self::AUGUST_BANK_HOLIDAY;
                break;
            case 12:
                if ($this->isChristmasDay($date))
                    $bankHoliday = self::CHRISTMAS_DAY;
                elseif ($this->isBoxingDay($date))
                    $bankHoliday = self::BOXING_DAY;
                break;
        }
        
        // If we haven't found a bank holiday, check the database
        if ($bankHoliday == self::NO_HOLIDAY) {
            $this->loadBankHolidaysFromDB($date->format('Y'));
            if (!empty($this->specialHolidays[$date->format('Y')][$date->format('Y-m-d')])) {
                $bankHoliday = self::SPECIAL_HOLIDAY;
            }
        }
        
        return $bankHoliday;
    }
    
    /**
     * Get the bank holiday name for a given date
     *
     * @param DateTime $date The date to search for// TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     *
     * @return string Bank holiday name, null if none
     */
    public function getBankHolidayName(DateTime $date)
    {
        $bankHoliday = $this->getBankHoliday($date);
        
        if (array_key_exists($bankHoliday, self::$bhNames))
            return self::$bhNames[$bankHoliday];
        elseif ($bankHoliday == self::SPECIAL_HOLIDAY) {
            $this->loadBankHolidaysFromDB($date->format('Y'));
            return $this->specialHolidays[$date->format('Y')][$date->format('Y-m-d')];
        }
        
        return null;
    }
    
    /**
     * Checks if the given date is a bank holiday
     *
     * @param DateTime $date The date to check // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     *
     * @return bool
     */
    public function isBankHoliday(DateTime $date)
    {
        return ($this->getBankHoliday($date) != self::NO_HOLIDAY);
    }
    
    /**
     * Load all special bank holidays for a given year
     *
     * @param int $year Year
     *
     * @return string[] Bank holidays in the format yyyy-mm-dd => "Bank holiday name"
     */
    private function loadBankHolidaysFromDB($year)
    {
        if (isset($this->specialHolidays[$year])) {
            return $this->specialHolidays[$year];
        }
        
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("bhdate, bhname");
        $query->from("bankholidays");
        $query->where(array(
            "bhdate >= '$year-01-01'",
            "bhdate <= '$year-12-31'"
        ));
        $db->setQuery($query);
        $db->execute();
        $result = $db->loadAssocList('bhdate', 'bhname');
        $this->specialHolidays[$year] = $result;
        return $result;
    }
    
    public function isNewYearsDay(DateTime $date)
    {
        if ($date->format('n') != 1) { // Check for January
            return false;
        }
        
        return (
            ($date->format('j') == 1 && $date->format('N') <= 5) || // Day 1 is a weekday
            ($date->format('N') == 1 && (
                $date->format('j') == 2 || $date->format('j') == 3 // Day 2 or 3 is Monday
            ))
        );
    }
    
    public function isGoodFriday(DateTime $date)
    {
        $date = $date->setTime(0, 0, 0);
        $easter = $this->getEasterSunday($date->format('Y'));
        return ($easter->sub("P2D") == $date);
    }
    
    public function isEasterMonday(DateTime $date)
    {
        $date = $date->setTime(0, 0, 0);
        $easter = $this->getEasterSunday($date->format('Y'));
        return ($easter->add("P1D") == $date);
    }
    
    /**
     * Get a date representing midnight on Easter Sunday
     *
     * @param int $year The year
     *
     * @return DateTime // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     */
    public function getEasterSunday($year)
    {
        return new DateTime('@'.easter_date($year));
    }
    
    public function isMayDay(DateTime $date)
    {
        if ($date->format('n') != 5) { // Check for May
            return false;
        }
        
        return ($date->format('N') == 1 && $date->format('j') <= 7); // A Monday within the first 7 days of the month
    }
    
    public function isSpringBankHoliday(DateTime $date)
    {
        if ($date->format('n') != 5) { // Check for May
            return false;
        }
        
        return ($date->format('N') == 1 && $date->format('j') >= (31-7)); // A Monday within the last 7 days of the month
    }
    
    public function isAugustBankHoliday(DateTime $date)
    {
        if ($date->format('n') != 8) { // Check for August
            return false;
        }
        
        return ($date->format('N') == 1 && $date->format('j') >= (31-7)); // A Monday within the last 7 days of the month
    }
    
    public function isChristmasDay(DateTime $date)
    {
        if ($date->format('n') != 12) { // Check for December
            return false;
        }
        
        return (
            ($date->format('N') <= 5 && $date->format('j') == 25) || // 25th December if weekday
            ($date->format('N') == 1 && (
                $date->format('j') == 26 || $date->format('j') == 27 // 26th/27th December if Monday
            ))
        );
    }
    
    public function isBoxingDay(DateTime $date)
    {
        if ($date->format('n') != 12) { // Check for December
            return false;
        }
        
        // Boxing day cannot fall on a Monday, unless Christmas Day is a Friday. In this case, Boxing Day is 28th(!) December
        return (
            ($date->format('N') > 1 && $date->format('N') <= 5 && $date->format('j') == 26) || // 26th December if weekday but not Monday
            ($date->format('N') == 2 && (
                $date->format('j') == 27 || $date->format('j') == 28 // 27th/28th December if Tuesday
            )) ||
            ($date->format('N') == 1 && $date->format('j') == 28)
        );
    }
}
