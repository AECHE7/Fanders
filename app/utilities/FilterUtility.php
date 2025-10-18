<?php

/**
 * FilterUtility Class
 * Provides common filtering utilities for index pages
 */
class FilterUtility
{
    /**
     * Sanitizes and validates common filter parameters
     * @param array $getParams The $_GET array
     * @return array Sanitized filter array
     */
    public static function sanitizeFilters(array $getParams): array
    {
        $filters = [];

        // Common text filters
        $filters['search'] = isset($getParams['search']) ? trim($getParams['search']) : '';

        // Status filter
        $filters['status'] = isset($getParams['status']) ? trim($getParams['status']) : '';

        // Type filter (for transactions)
        $filters['type'] = isset($getParams['type']) ? trim($getParams['type']) : '';

        // Date filters
        $filters['date_from'] = isset($getParams['date_from']) ? trim($getParams['date_from']) : '';
        $filters['date_to'] = isset($getParams['date_to']) ? trim($getParams['date_to']) : '';

        // Numeric filters
        $filters['client_id'] = isset($getParams['client_id']) ? (int)$getParams['client_id'] : 0;
        $filters['loan_id'] = isset($getParams['loan_id']) ? (int)$getParams['loan_id'] : 0;
        $filters['recorded_by'] = isset($getParams['recorded_by']) ? (int)$getParams['recorded_by'] : 0;

        // Pagination
        $filters['page'] = isset($getParams['page']) ? max(1, (int)$getParams['page']) : 1;
        $filters['limit'] = isset($getParams['limit']) ? min(100, max(10, (int)$getParams['limit'])) : 50;

        return $filters;
    }

    /**
     * Validates date range filters
     * @param array $filters The filter array
     * @return array Validated filters with corrected dates
     */
    public static function validateDateRange(array $filters): array
    {
        // Validate date formats
        if (!empty($filters['date_from']) && !strtotime($filters['date_from'])) {
            $filters['date_from'] = '';
        }
        if (!empty($filters['date_to']) && !strtotime($filters['date_to'])) {
            $filters['date_to'] = '';
        }

        // Ensure date_from is not after date_to
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
                // Swap dates
                $temp = $filters['date_from'];
                $filters['date_from'] = $filters['date_to'];
                $filters['date_to'] = $temp;
            }
        }

        return $filters;
    }

    /**
     * Builds SQL WHERE conditions from filters
     * @param array $filters The filter array
     * @param array $allowedStatuses Optional array of allowed status values
     * @return array [whereClause, params]
     */
    public static function buildWhereClause(array $filters, array $allowedStatuses = []): array
    {
        $conditions = [];
        $params = [];

        // Search filter (generic text search)
        if (!empty($filters['search'])) {
            // This should be customized per table/entity
            $conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchParam = '%' . $filters['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Status filter
        if (!empty($filters['status'])) {
            if (empty($allowedStatuses) || in_array($filters['status'], $allowedStatuses)) {
                $conditions[] = "status = ?";
                $params[] = $filters['status'];
            }
        }

        // Type filter
        if (!empty($filters['type'])) {
            $conditions[] = "type = ?";
            $params[] = $filters['type'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $conditions[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Numeric ID filters
        if (!empty($filters['client_id'])) {
            $conditions[] = "client_id = ?";
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['loan_id'])) {
            $conditions[] = "loan_id = ?";
            $params[] = $filters['loan_id'];
        }
        if (!empty($filters['recorded_by'])) {
            $conditions[] = "recorded_by = ?";
            $params[] = $filters['recorded_by'];
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [$whereClause, $params];
    }

    /**
     * Gets pagination info
     * @param array $filters The filter array
     * @param int $totalRecords Total number of records
     * @return array Pagination info
     */
    public static function getPaginationInfo(array $filters, int $totalRecords): array
    {
        $totalPages = ceil($totalRecords / $filters['limit']);
        $currentPage = min($filters['page'], $totalPages);
        $offset = ($currentPage - 1) * $filters['limit'];

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $filters['limit'],
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages
        ];
    }
}
