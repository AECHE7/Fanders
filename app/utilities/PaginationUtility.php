<?php
/**
 * Pagination Utility Class
 * Provides pagination functionality for lists
 */

class PaginationUtility
{
    private $baseUrl;
    private $currentPage;
    private $totalItems;
    private $itemsPerPage;
    private $maxPages;

    public function __construct($baseUrl = '', $currentPage = 1, $totalItems = 0, $itemsPerPage = 20, $maxPages = 10)
    {
        $this->baseUrl = $baseUrl;
        $this->currentPage = (int)$currentPage;
        $this->totalItems = (int)$totalItems;
        $this->itemsPerPage = (int)$itemsPerPage;
        $this->maxPages = (int)$maxPages;
    }

    /**
     * Get total number of pages
     */
    public function getTotalPages()
    {
        return ceil($this->totalItems / $this->itemsPerPage);
    }

    /**
     * Get current page number
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get items per page
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * Get offset for database query
     */
    public function getOffset()
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Check if there are more pages
     */
    public function hasNextPage()
    {
        return $this->currentPage < $this->getTotalPages();
    }

    /**
     * Check if there are previous pages
     */
    public function hasPrevPage()
    {
        return $this->currentPage > 1;
    }

    /**
     * Get next page number
     */
    public function getNextPage()
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    /**
     * Get previous page number
     */
    public function getPrevPage()
    {
        return $this->hasPrevPage() ? $this->currentPage - 1 : null;
    }

    /**
     * Get page range for display
     */
    public function getPageRange()
    {
        $totalPages = $this->getTotalPages();
        $halfMax = floor($this->maxPages / 2);

        $start = max(1, $this->currentPage - $halfMax);
        $end = min($totalPages, $start + $this->maxPages - 1);

        // Adjust start if we're near the end
        $start = max(1, $end - $this->maxPages + 1);

        return range($start, $end);
    }

    /**
     * Generate pagination HTML
     */
    public function render($additionalParams = [])
    {
        $totalPages = $this->getTotalPages();

        if ($totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous button
        if ($this->hasPrevPage()) {
            $prevUrl = $this->buildUrl($this->getPrevPage(), $additionalParams);
            $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">&laquo; Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
        }

        // Page numbers
        foreach ($this->getPageRange() as $page) {
            if ($page == $this->currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } else {
                $url = $this->buildUrl($page, $additionalParams);
                $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">' . $page . '</a></li>';
            }
        }

        // Next button
        if ($this->hasNextPage()) {
            $nextUrl = $this->buildUrl($this->getNextPage(), $additionalParams);
            $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next &raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Build URL for pagination links
     */
    private function buildUrl($page, $additionalParams = [])
    {
        $params = array_merge($additionalParams, ['page' => $page]);

        if (empty($this->baseUrl)) {
            $url = $_SERVER['PHP_SELF'];
        } else {
            $url = $this->baseUrl;
        }

        $queryString = http_build_query($params);

        return $url . '?' . $queryString;
    }

    /**
     * Get pagination info for display
     */
    public function getInfo()
    {
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->itemsPerPage, $this->totalItems);

        return "Showing {$start} to {$end} of {$this->totalItems} entries";
    }
}
