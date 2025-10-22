<?php

/**
 * Enhanced FilterUtility Class
 * Provides comprehensive filtering utilities for all endpoints with proper SQL generation
 */
class FilterUtility
{
    // Default pagination settings
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 100;
    public const MIN_LIMIT = 10;

    // Common filter field mappings
    public const FIELD_MAPPINGS = [
        'loans' => [
            'search_fields' => ['c.name', 'c.email', 'c.phone_number', 'l.id'],
            'date_field' => 'l.created_at',
            'status_field' => 'l.status',
            'client_field' => 'l.client_id'
        ],
        'clients' => [
            'search_fields' => ['name', 'email', 'phone_number', 'address'],
            'date_field' => 'created_at',
            'status_field' => 'status'
        ],
        'payments' => [
            'search_fields' => ['c.name', 'c.email', 'p.id'],
            'date_field' => 'p.payment_date',
            'loan_field' => 'p.loan_id',
            'client_field' => 'l.client_id'
        ],
        'users' => [
            'search_fields' => ['name', 'email', 'username'],
            'date_field' => 'created_at',
            'role_field' => 'role'
        ]
    ];

    /**
     * Enhanced filter sanitization with more options
     * @param array $getParams The $_GET array
     * @param array $options Configuration options for specific endpoint
     * @return array Sanitized filter array
     */
    public static function sanitizeFilters(array $getParams, array $options = []): array
    {
        $filters = [];

        // Common text filters
        $filters['search'] = isset($getParams['search']) ? trim($getParams['search']) : '';

        // Status filter with validation
        $filters['status'] = isset($getParams['status']) ? trim($getParams['status']) : '';
        if (!empty($options['allowed_statuses']) && !empty($filters['status'])) {
            if (!in_array($filters['status'], $options['allowed_statuses'])) {
                $filters['status'] = '';
            }
        }

        // Type filter (for transactions, etc.)
        $filters['type'] = isset($getParams['type']) ? trim($getParams['type']) : '';

        // Role filter (for users)
        $filters['role'] = isset($getParams['role']) ? trim($getParams['role']) : '';

        // Date filters with better handling
        $filters['date_from'] = isset($getParams['date_from']) ? trim($getParams['date_from']) : '';
        $filters['date_to'] = isset($getParams['date_to']) ? trim($getParams['date_to']) : '';
        
        // Alternative date field names for compatibility
        if (empty($filters['date_from']) && isset($getParams['start_date'])) {
            $filters['date_from'] = trim($getParams['start_date']);
        }
        if (empty($filters['date_to']) && isset($getParams['end_date'])) {
            $filters['date_to'] = trim($getParams['end_date']);
        }

        // Numeric filters with validation
        $filters['client_id'] = isset($getParams['client_id']) ? max(0, (int)$getParams['client_id']) : 0;
        $filters['loan_id'] = isset($getParams['loan_id']) ? max(0, (int)$getParams['loan_id']) : 0;
        $filters['user_id'] = isset($getParams['user_id']) ? max(0, (int)$getParams['user_id']) : 0;
        $filters['recorded_by'] = isset($getParams['recorded_by']) ? max(0, (int)$getParams['recorded_by']) : 0;

        // Amount range filters
        $filters['amount_min'] = isset($getParams['amount_min']) ? max(0, (float)$getParams['amount_min']) : 0;
        $filters['amount_max'] = isset($getParams['amount_max']) ? max(0, (float)$getParams['amount_max']) : 0;

        // Pagination with enhanced validation
        $filters['page'] = isset($getParams['page']) ? max(1, (int)$getParams['page']) : 1;
        $filters['limit'] = isset($getParams['limit']) ? 
            min(self::MAX_LIMIT, max(self::MIN_LIMIT, (int)$getParams['limit'])) : 
            self::DEFAULT_LIMIT;

        // Sort options
        $filters['sort_by'] = isset($getParams['sort_by']) ? trim($getParams['sort_by']) : '';
        $filters['sort_order'] = isset($getParams['sort_order']) && 
            in_array(strtoupper($getParams['sort_order']), ['ASC', 'DESC']) ? 
            strtoupper($getParams['sort_order']) : 'DESC';

        return $filters;
    }

    /**
     * Enhanced date range validation with better error handling
     * @param array $filters The filter array
     * @return array Validated filters with corrected dates and validation info
     */
    public static function validateDateRange(array $filters): array
    {
        $validationInfo = ['date_errors' => []];

        // Validate date formats
        if (!empty($filters['date_from'])) {
            if (!strtotime($filters['date_from'])) {
                $validationInfo['date_errors'][] = 'Invalid start date format';
                $filters['date_from'] = '';
            }
        }
        
        if (!empty($filters['date_to'])) {
            if (!strtotime($filters['date_to'])) {
                $validationInfo['date_errors'][] = 'Invalid end date format';
                $filters['date_to'] = '';
            }
        }

        // Ensure date_from is not after date_to
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
                // Swap dates
                $temp = $filters['date_from'];
                $filters['date_from'] = $filters['date_to'];
                $filters['date_to'] = $temp;
                $validationInfo['date_errors'][] = 'Date range was automatically corrected';
            }
        }

        // Check for reasonable date ranges (not more than 2 years)
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $daysDiff = (strtotime($filters['date_to']) - strtotime($filters['date_from'])) / (60 * 60 * 24);
            if ($daysDiff > 730) { // 2 years
                $validationInfo['date_errors'][] = 'Date range too large (max 2 years)';
                $filters['date_to'] = date('Y-m-d', strtotime($filters['date_from'] . ' +1 year'));
            }
        }

        $filters['_validation'] = $validationInfo;
        return $filters;
    }

    /**
     * Enhanced SQL WHERE clause builder with flexible field mapping
     * @param array $filters The filter array
     * @param string $entityType Entity type (loans, clients, payments, users)
     * @param array $customMappings Custom field mappings to override defaults
     * @return array [whereClause, params]
     */
    public static function buildWhereClause(array $filters, string $entityType, array $customMappings = []): array
    {
        $conditions = [];
        $params = [];

        // Get field mappings for this entity type
        $mappings = array_merge(
            self::FIELD_MAPPINGS[$entityType] ?? [],
            $customMappings
        );

        // Search filter with dynamic field mapping
        if (!empty($filters['search']) && !empty($mappings['search_fields'])) {
            $searchConditions = [];
            $searchParam = '%' . $filters['search'] . '%';
            
            foreach ($mappings['search_fields'] as $field) {
                $searchConditions[] = "$field LIKE ?";
                $params[] = $searchParam;
            }
            
            if (!empty($searchConditions)) {
                $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
        }

        // Status filter - supports both single value and array
        if (!empty($filters['status']) && !empty($mappings['status_field'])) {
            if (is_array($filters['status'])) {
                // Handle array of statuses with IN clause
                $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
                $conditions[] = "{$mappings['status_field']} IN ($placeholders)";
                foreach ($filters['status'] as $status) {
                    $params[] = $status;
                }
            } else {
                // Handle single status value
                $conditions[] = "{$mappings['status_field']} = ?";
                $params[] = $filters['status'];
            }
        }

        // Role filter (for users)
        if (!empty($filters['role']) && !empty($mappings['role_field'])) {
            $conditions[] = "{$mappings['role_field']} = ?";
            $params[] = $filters['role'];
        }

        // Type filter
        if (!empty($filters['type']) && !empty($mappings['type_field'])) {
            $conditions[] = "{$mappings['type_field']} = ?";
            $params[] = $filters['type'];
        }

        // Date range filters with dynamic field mapping
        $dateField = $mappings['date_field'] ?? 'created_at';
        if (!empty($filters['date_from'])) {
            $conditions[] = "$dateField >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "$dateField <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Numeric ID filters
        if (!empty($filters['client_id']) && !empty($mappings['client_field'])) {
            $conditions[] = "{$mappings['client_field']} = ?";
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['loan_id']) && !empty($mappings['loan_field'])) {
            $conditions[] = "{$mappings['loan_field']} = ?";
            $params[] = $filters['loan_id'];
        }
        if (!empty($filters['user_id']) && !empty($mappings['user_field'])) {
            $conditions[] = "{$mappings['user_field']} = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['recorded_by']) && !empty($mappings['recorded_by_field'])) {
            $conditions[] = "{$mappings['recorded_by_field']} = ?";
            $params[] = $filters['recorded_by'];
        }

        // Amount range filters
        if (!empty($filters['amount_min']) && !empty($mappings['amount_field'])) {
            $conditions[] = "{$mappings['amount_field']} >= ?";
            $params[] = $filters['amount_min'];
        }
        if (!empty($filters['amount_max']) && !empty($mappings['amount_field'])) {
            $conditions[] = "{$mappings['amount_field']} <= ?";
            $params[] = $filters['amount_max'];
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [$whereClause, $params];
    }

    /**
     * Build ORDER BY clause with validation
     * @param array $filters Filter array containing sort options
     * @param array $allowedSortFields Array of allowed sort fields
     * @param string $defaultSort Default sort field
     * @return string ORDER BY clause
     */
    public static function buildOrderClause(array $filters, array $allowedSortFields, string $defaultSort = 'created_at'): string
    {
        $sortBy = $defaultSort;
        $sortOrder = $filters['sort_order'] ?? 'DESC';

        if (!empty($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortFields)) {
            $sortBy = $filters['sort_by'];
        }

        return "ORDER BY $sortBy $sortOrder";
    }

    /**
     * Build LIMIT clause for pagination
     * @param array $filters Filter array containing pagination info
     * @return string LIMIT clause
     */
    public static function buildLimitClause(array $filters): string
    {
        $limit = $filters['limit'] ?? self::DEFAULT_LIMIT;
        $offset = (($filters['page'] ?? 1) - 1) * $limit;
        
        return "LIMIT $limit OFFSET $offset";
    }
    /**
     * Enhanced pagination info with more details
     * @param array $filters The filter array
     * @param int $totalRecords Total number of records
     * @return array Comprehensive pagination info
     */
    public static function getPaginationInfo(array $filters, int $totalRecords): array
    {
        $limit = $filters['limit'] ?? self::DEFAULT_LIMIT;
        $currentPage = $filters['page'] ?? 1;
        $totalPages = max(1, ceil($totalRecords / $limit));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'showing_from' => $totalRecords > 0 ? $offset + 1 : 0,
            'showing_to' => min($offset + $limit, $totalRecords),
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'page_range' => self::getPageRange($currentPage, $totalPages)
        ];
    }

    /**
     * Get page range for pagination navigation
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param int $range Number of pages to show around current page
     * @return array Array of page numbers to display
     */
    public static function getPageRange(int $currentPage, int $totalPages, int $range = 5): array
    {
        $start = max(1, $currentPage - floor($range / 2));
        $end = min($totalPages, $start + $range - 1);
        
        // Adjust start if we're near the end
        $start = max(1, $end - $range + 1);
        
        return range($start, $end);
    }

    /**
     * Generate filter summary for display
     * @param array $filters Applied filters
     * @return array Filter summary for UI display
     */
    public static function getFilterSummary(array $filters): array
    {
        $summary = [];

        if (!empty($filters['search'])) {
            $summary[] = "Search: '{$filters['search']}'";
        }

        if (!empty($filters['status'])) {
            $summary[] = "Status: " . ucfirst($filters['status']);
        }

        if (!empty($filters['role'])) {
            $summary[] = "Role: " . ucfirst($filters['role']);
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $dateRange = '';
            if (!empty($filters['date_from'])) {
                $dateRange .= date('M d, Y', strtotime($filters['date_from']));
            }
            if (!empty($filters['date_to'])) {
                $dateRange .= ' to ' . date('M d, Y', strtotime($filters['date_to']));
            }
            $summary[] = "Date: $dateRange";
        }

        if (!empty($filters['client_id'])) {
            $summary[] = "Client ID: {$filters['client_id']}";
        }

        if (!empty($filters['loan_id'])) {
            $summary[] = "Loan ID: {$filters['loan_id']}";
        }

        return $summary;
    }

    /**
     * Clean filters for URL generation (remove empty values)
     * @param array $filters Filter array
     * @return array Cleaned filter array
     */
    public static function cleanFiltersForUrl(array $filters): array
    {
        $cleaned = [];
        
        foreach ($filters as $key => $value) {
            if ($key === '_validation') continue; // Skip internal validation data
            
            if (is_string($value) && trim($value) !== '') {
                $cleaned[$key] = $value;
            } elseif (is_numeric($value) && $value > 0) {
                $cleaned[$key] = $value;
            } elseif ($key === 'page' && $value > 1) {
                $cleaned[$key] = $value;
            } elseif ($key === 'limit' && $value !== self::DEFAULT_LIMIT) {
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }

    /**
     * Build query string from filters
     * @param array $filters Filter array
     * @param array $exclude Keys to exclude from query string
     * @return string Query string
     */
    public static function buildQueryString(array $filters, array $exclude = []): string
    {
        $cleaned = self::cleanFiltersForUrl($filters);
        
        foreach ($exclude as $key) {
            unset($cleaned[$key]);
        }
        
        return http_build_query($cleaned);
    }
}
