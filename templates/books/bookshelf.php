<?php
/**
 * Bookshelf template - Visual book browsing interface
 */
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Bookshelf View</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="view-grid-btn">
                    <i data-feather="grid"></i> Grid
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary active" id="view-shelf-btn">
                    <i data-feather="book-open"></i> Shelf
                </button>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="notion-alert notion-alert-info p-3">
                <div class="d-flex">
                    <div class="me-3">
                        <i data-feather="info" class="text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Interactive Bookshelf View</h5>
                        <p class="mb-0">Browse books visually like on a real bookshelf. Click on any book to see detailed information. You can filter by category using the dropdown menu below.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="bookshelf-filters">
                <div class="d-flex align-items-center">
                    <label for="categoryFilter" class="form-label me-2 mb-0">Filter by Category:</label>
                    <select class="form-select form-select-sm w-auto" id="categoryFilter">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?> (<?= $category['book_count'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ms-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="availableOnlyToggle">
                            <label class="form-check-label" for="availableOnlyToggle">Available only</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="form-control" id="searchBooks" placeholder="Search books...">
                <button class="btn btn-outline-secondary" type="button">
                    <i data-feather="search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Bookshelf Container -->
    <div class="bookshelf-container">
        <?php 
        // Group books by category for display
        $booksByCategory = [];
        if ($books) {
            foreach ($books as $book) {
                $categoryId = $book['category_id'];
                $categoryName = $book['category_name'] ?? 'Uncategorized';
                
                if (!isset($booksByCategory[$categoryId])) {
                    $booksByCategory[$categoryId] = [
                        'name' => $categoryName,
                        'books' => []
                    ];
                }
                
                $booksByCategory[$categoryId]['books'][] = $book;
            }
        }
        
        // Display bookshelves by category
        foreach ($booksByCategory as $categoryId => $category): 
        ?>
            <div class="bookshelf-section" data-category-id="<?= $categoryId ?>">
                <div class="bookshelf-row">
                    <div class="bookshelf-category"><?= htmlspecialchars($category['name']) ?></div>
                    <?php if (empty($category['books'])): ?>
                        <div class="empty-shelf">No books in this category</div>
                    <?php else: ?>
                        <?php foreach ($category['books'] as $index => $book): 
                            // Generate consistent color based on title
                            $colorIndex = (crc32($book['title']) % 10) + 1;
                        ?>
                            <div class="book book-color-<?= $colorIndex ?>" data-book-id="<?= $book['id'] ?>" data-available="<?= $book['is_available'] ?>">
                                <div class="book-spine">
                                    <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
                                    <div class="book-available <?= $book['is_available'] ? '' : 'book-unavailable' ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($booksByCategory)): ?>
            <div class="alert alert-info">No books available. Please add some books first.</div>
        <?php endif; ?>
    </div>

    <!-- Book Detail Popup -->
    <div class="book-overlay" id="bookOverlay"></div>
    <div class="book-detail" id="bookDetail">
        <div class="book-detail-header">
            <div class="book-detail-title" id="bookDetailTitle"></div>
            <div class="book-detail-author" id="bookDetailAuthor"></div>
        </div>
        <div class="book-detail-content">
            <div class="book-detail-info">
                <div class="book-detail-label">ISBN:</div>
                <div class="book-detail-value" id="bookDetailIsbn"></div>
            </div>
            <div class="book-detail-info">
                <div class="book-detail-label">Category:</div>
                <div class="book-detail-value" id="bookDetailCategory"></div>
            </div>
            <div class="book-detail-info">
                <div class="book-detail-label">Publisher:</div>
                <div class="book-detail-value" id="bookDetailPublisher"></div>
            </div>
            <div class="book-detail-info">
                <div class="book-detail-label">Year:</div>
                <div class="book-detail-value" id="bookDetailYear"></div>
            </div>
            <div class="book-detail-info">
                <div class="book-detail-label">Availability:</div>
                <div class="book-detail-value" id="bookDetailAvailability"></div>
            </div>
            <div class="book-detail-description" id="bookDetailDescription"></div>
        </div>
        <div class="book-detail-footer">
            <button class="btn btn-sm btn-outline-secondary" id="closeBookDetail">Close</button>
            <a href="#" class="btn btn-sm btn-primary" id="borrowBookBtn">Borrow Book</a>
        </div>
    </div>
</main>

<script>
// Bookshelf JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const books = document.querySelectorAll('.book');
    const bookDetail = document.getElementById('bookDetail');
    const bookOverlay = document.getElementById('bookOverlay');
    const closeBookDetail = document.getElementById('closeBookDetail');
    const borrowBookBtn = document.getElementById('borrowBookBtn');
    const categoryFilter = document.getElementById('categoryFilter');
    const availableOnlyToggle = document.getElementById('availableOnlyToggle');
    const searchBooks = document.getElementById('searchBooks');
    
    // Book detail elements
    const bookDetailTitle = document.getElementById('bookDetailTitle');
    const bookDetailAuthor = document.getElementById('bookDetailAuthor');
    const bookDetailIsbn = document.getElementById('bookDetailIsbn');
    const bookDetailCategory = document.getElementById('bookDetailCategory');
    const bookDetailPublisher = document.getElementById('bookDetailPublisher');
    const bookDetailYear = document.getElementById('bookDetailYear');
    const bookDetailAvailability = document.getElementById('bookDetailAvailability');
    const bookDetailDescription = document.getElementById('bookDetailDescription');
    
    // Book click event
    books.forEach(book => {
        book.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            fetchBookDetails(bookId);
        });
    });
    
    // Close detail panel
    closeBookDetail.addEventListener('click', function() {
        bookDetail.classList.remove('active');
        bookOverlay.classList.remove('active');
    });
    
    bookOverlay.addEventListener('click', function() {
        bookDetail.classList.remove('active');
        bookOverlay.classList.remove('active');
    });
    
    // Category filter
    categoryFilter.addEventListener('change', function() {
        filterBooks();
    });
    
    // Available only toggle
    availableOnlyToggle.addEventListener('change', function() {
        filterBooks();
    });
    
    // Search books
    searchBooks.addEventListener('input', function() {
        filterBooks();
    });
    
    // Filter books function
    function filterBooks() {
        const category = categoryFilter.value;
        const availableOnly = availableOnlyToggle.checked;
        const searchTerm = searchBooks.value.toLowerCase();
        
        // Show/hide book sections based on category
        const sections = document.querySelectorAll('.bookshelf-section');
        sections.forEach(section => {
            if (category === 'all' || section.dataset.categoryId === category) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
        
        // Filter individual books by availability and search term
        books.forEach(book => {
            const bookTitle = book.querySelector('.book-title').textContent.toLowerCase();
            const bookAuthor = book.querySelector('.book-author').textContent.toLowerCase();
            const isAvailable = book.dataset.available === '1';
            const matchesSearch = bookTitle.includes(searchTerm) || bookAuthor.includes(searchTerm) || searchTerm === '';
            
            if ((availableOnly && !isAvailable) || !matchesSearch) {
                book.style.display = 'none';
            } else {
                book.style.display = 'block';
            }
        });
    }
    
    // Fetch book details
    function fetchBookDetails(bookId) {
        fetch(`${APP_URL}/public/api/books.php?id=${bookId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookDetails(data.book);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error fetching book details:', error));
    }
    
    // Display book details
    function displayBookDetails(book) {
        bookDetailTitle.textContent = book.title;
        bookDetailAuthor.textContent = book.author;
        bookDetailIsbn.textContent = book.isbn;
        bookDetailCategory.textContent = book.category_name;
        bookDetailPublisher.textContent = book.publisher || 'Not specified';
        bookDetailYear.textContent = book.publication_year || 'Not specified';
        
        // Availability display
        if (book.is_available == 1) {
            bookDetailAvailability.innerHTML = `<span class="badge bg-success">Available</span> (${book.available_copies} of ${book.total_copies} copies)`;
            borrowBookBtn.style.display = 'block';
            borrowBookBtn.href = `${APP_URL}/public/transactions/borrow.php?book_id=${book.id}`;
        } else {
            bookDetailAvailability.innerHTML = `<span class="badge bg-danger">Not Available</span> (0 of ${book.total_copies} copies)`;
            borrowBookBtn.style.display = 'none';
        }
        
        bookDetailDescription.textContent = book.description || 'No description available.';
        
        // Show popup
        bookDetail.classList.add('active');
        bookOverlay.classList.add('active');
    }
});
</script>
