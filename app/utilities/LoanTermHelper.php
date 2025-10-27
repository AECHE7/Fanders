<?php
/**
 * LoanTermHelper - Utility class for converting loan terms to conversational formats
 */

class LoanTermHelper {
    
    /**
     * Convert weeks to a conversational term
     * @param int $weeks Number of weeks
     * @return string Conversational term
     */
    public static function weeksToConversational($weeks) {
        // Common conversational mappings
        $conversionalTerms = [
            4 => '1 month',
            8 => '2 months', 
            12 => '3 months',
            16 => '4 months',
            17 => '4+ months (17 weeks)',
            20 => '5 months',
            24 => '6 months',
            26 => '6+ months (26 weeks)',
            52 => '1 year'
        ];
        
        // Check for exact matches first
        if (isset($conversionalTerms[$weeks])) {
            return $conversionalTerms[$weeks];
        }
        
        // Calculate approximate months for other values
        $months = round($weeks / 4.33, 1);
        
        if ($months < 1) {
            return $weeks . ' weeks';
        } elseif ($months == 1) {
            return '1 month (' . $weeks . ' weeks)';
        } elseif ($months == round($months)) {
            return round($months) . ' months (' . $weeks . ' weeks)';
        } else {
            return $months . ' months (' . $weeks . ' weeks)';
        }
    }
    
    /**
     * Get common loan term options with conversational labels
     * @return array Array of [weeks => label] pairs
     */
    public static function getCommonTermOptions() {
        return [
            4 => '1 month (4 weeks)',
            8 => '2 months (8 weeks)',
            12 => '3 months (12 weeks)',
            16 => '4 months (16 weeks)',
            17 => '4+ months (17 weeks) - Standard',
            20 => '5 months (20 weeks)',
            24 => '6 months (24 weeks)',
            26 => '6+ months (26 weeks)',
            52 => '1 year (52 weeks)'
        ];
    }
}