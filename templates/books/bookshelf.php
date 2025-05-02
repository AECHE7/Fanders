<?php
/**
 * Bookshelf visualization template
 * Displays books in a skeuomorphic animated bookshelf
 */
?>

<div class="bookshelf-container mb-5">
    <!-- Bookshelf Header with Controls -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; background-color: #f7ecff;">
                <i data-feather="book-open" style="width: 20px; height: 20px; color: #9d71ea;"></i>
            </div>
            <h4 class="mb-0">Library Bookshelf</h4>
        </div>
        
        <div class="bookshelf-controls d-flex align-items-center">
            <!-- Category Filter -->
            <div class="me-3">
                <select class="form-select form-select-sm" id="bookshelf-category-filter">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Search Box -->
            <div class="input-group input-group-sm me-3 search-box" style="width: 250px;">
                <input type="text" class="form-control" id="bookshelf-search" placeholder="Search books...">
                <span class="input-group-text bg-white">
                    <i data-feather="search" style="width: 14px; height: 14px;"></i>
                </span>
            </div>
            
            <!-- View Toggle -->
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary active" id="bookshelf-view-btn" title="Bookshelf View">
                    <i data-feather="layers" style="width: 14px; height: 14px;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="list-view-btn" title="List View">
                    <i data-feather="list" style="width: 14px; height: 14px;"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bookshelf View -->
    <div id="bookshelf-view" class="mb-4">
        <?php for ($shelf = 0; $shelf < ceil(count($books) / 6); $shelf++): ?>
            <div class="bookshelf mb-5">
                <div class="shelf">
                    <?php 
                    $start = $shelf * 6;
                    $end = min(($shelf + 1) * 6, count($books));
                    for ($i = $start; $i < $end; $i++): 
                        $book = $books[$i];
                        
                        // Generate a color based on the category id or any other attribute
                        $colors = ['#9d71ea', '#0ca789', '#ec7211', '#0b76ef', '#5a67d8', '#e05252'];
                        $colorIndex = $book['category_id'] % count($colors);
                        $bookColor = $colors[$colorIndex];
                        
                        // Calculate book height based on total_copies (more copies = thicker book)
                        $minHeight = 180;
                        $maxHeight = 220;
                        $height = $minHeight + min(($book['total_copies'] * 5), ($maxHeight - $minHeight));
                    ?>
                        <div class="book" 
                             data-id="<?= $book['id'] ?>"
                             data-category="<?= $book['category_id'] ?>"
                             data-title="<?= htmlspecialchars($book['title']) ?>"
                             data-author="<?= htmlspecialchars($book['author']) ?>"
                             style="height: <?= $height ?>px;">
                            <div class="book-spine" style="background-color: <?= $bookColor ?>">
                                <span class="book-title"><?= htmlspecialchars($book['title']) ?></span>
                                <span class="book-author"><?= htmlspecialchars($book['author']) ?></span>
                            </div>
                            <div class="book-side"></div>
                            <div class="book-cover">
                                <h5 class="book-cover-title"><?= htmlspecialchars($book['title']) ?></h5>
                                <p class="book-cover-author"><?= htmlspecialchars($book['author']) ?></p>
                                <div class="book-cover-footer">
                                    <span class="book-isbn"><?= $book['isbn'] ? htmlspecialchars($book['isbn']) : 'No ISBN' ?></span>
                                    <span class="book-year"><?= $book['publication_year'] ? htmlspecialchars($book['publication_year']) : '' ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="shelf-edge"></div>
                <div class="shelf-shadow"></div>
            </div>
        <?php endfor; ?>
        
        <?php if (empty($books)): ?>
            <div class="empty-bookshelf text-center p-5 bg-light rounded">
                <i data-feather="book" style="width: 48px; height: 48px; color: #ccc;" class="mb-3"></i>
                <h5>Bookshelf is Empty</h5>
                <p class="text-muted">No books found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- List View (Hidden by Default) -->
    <div id="list-view" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (!empty($books)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Copies</th>
                                <th>Available</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="text-decoration-none fw-medium">
                                            <?= htmlspecialchars($book['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['category_name']) ?></td>
                                    <td><?= $book['total_copies'] ?></td>
                                    <td>
                                        <?php if ($book['available_copies'] > 0): ?>
                                            <span class="badge bg-success"><?= $book['available_copies'] ?> Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-5">
                    <i data-feather="book" style="width: 32px; height: 32px; color: #ccc;" class="mb-3"></i>
                    <p class="text-muted">No books found matching your criteria.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Book Detail Modal -->
<div class="modal fade" id="bookDetailModal" tabindex="-1" aria-labelledby="bookDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="bookDetailModalLabel">Book Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4 book-modal-cover">
                    <div class="book-modal-spine" style="background-color: #9d71ea">
                        <span class="book-modal-title">Book Title</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h5 id="modal-book-title">Book Title</h5>
                    <p class="text-muted" id="modal-book-author">Author Name</p>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted d-block">Category</small>
                            <span id="modal-book-category">Fiction</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted d-block">ISBN</small>
                            <span id="modal-book-isbn">978-3-16-148410-0</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted d-block">Published</small>
                            <span id="modal-book-year">2023</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted d-block">Availability</small>
                            <span id="modal-book-availability">3 of 5 available</span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Description</h6>
                    <p id="modal-book-description" class="text-muted small">No description available.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="modal-book-view-link">View Details</a>
            </div>
        </div>
    </div>
</div>

<!-- Bookshelf & Book Animation Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize feather icons
        feather.replace();
        
        // Get DOM elements
        const searchInput = document.getElementById('bookshelf-search');
        const categoryFilter = document.getElementById('bookshelf-category-filter');
        const bookshelfViewBtn = document.getElementById('bookshelf-view-btn');
        const listViewBtn = document.getElementById('list-view-btn');
        const bookshelfView = document.getElementById('bookshelf-view');
        const listView = document.getElementById('list-view');
        const allBooks = document.querySelectorAll('.book');
        
        // Modal elements
        const modal = new bootstrap.Modal(document.getElementById('bookDetailModal'));
        const modalTitle = document.getElementById('modal-book-title');
        const modalAuthor = document.getElementById('modal-book-author');
        const modalCategory = document.getElementById('modal-book-category');
        const modalIsbn = document.getElementById('modal-book-isbn');
        const modalYear = document.getElementById('modal-book-year');
        const modalAvailability = document.getElementById('modal-book-availability');
        const modalDescription = document.getElementById('modal-book-description');
        const modalViewLink = document.getElementById('modal-book-view-link');
        const modalSpine = document.querySelector('.book-modal-spine');
        const modalBookTitle = document.querySelector('.book-modal-title');
        
        // Toggle between bookshelf and list views
        bookshelfViewBtn.addEventListener('click', function() {
            bookshelfView.classList.remove('d-none');
            listView.classList.add('d-none');
            bookshelfViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
        });
        
        listViewBtn.addEventListener('click', function() {
            bookshelfView.classList.add('d-none');
            listView.classList.remove('d-none');
            bookshelfViewBtn.classList.remove('active');
            listViewBtn.classList.add('active');
        });
        
        // Filter books by search term
        searchInput.addEventListener('input', filterBooks);
        
        // Filter books by category
        categoryFilter.addEventListener('change', filterBooks);
        
        // Function to filter books based on search and category
        function filterBooks() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryId = categoryFilter.value;
            
            allBooks.forEach(book => {
                const title = book.getAttribute('data-title').toLowerCase();
                const author = book.getAttribute('data-author').toLowerCase();
                const category = book.getAttribute('data-category');
                
                // Check if book matches search term and category
                const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm);
                const matchesCategory = categoryId === 'all' || category === categoryId;
                
                // Show or hide the book
                if (matchesSearch && matchesCategory) {
                    book.style.display = 'block';
                } else {
                    book.style.display = 'none';
                }
            });
        }
        
        // Add click event to each book
        allBooks.forEach(book => {
            book.addEventListener('click', function() {
                // Get book data from attributes
                const bookId = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const author = this.getAttribute('data-author');
                
                // Get book color
                const bookSpine = this.querySelector('.book-spine');
                const bookColor = window.getComputedStyle(bookSpine).backgroundColor;
                
                // Set modal content
                modalTitle.textContent = title;
                modalAuthor.textContent = author;
                
                // Attempt to fetch additional book details via AJAX
                fetch(`${APP_URL}/public/api/books.php?id=${bookId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            modalCategory.textContent = book.category_name || 'Unknown';
                            modalIsbn.textContent = book.isbn || 'N/A';
                            modalYear.textContent = book.publication_year || 'Unknown';
                            modalAvailability.textContent = `${book.available_copies} of ${book.total_copies} available`;
                            modalDescription.textContent = book.description || 'No description available.';
                            
                            // Update availability badge color
                            if (book.available_copies > 0) {
                                modalAvailability.className = 'text-success';
                            } else {
                                modalAvailability.className = 'text-danger';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching book details:', error);
                    });
                
                // Set view link
                modalViewLink.href = `${APP_URL}/public/books/view.php?id=${bookId}`;
                
                // Set modal book spine color
                modalSpine.style.backgroundColor = bookColor;
                modalBookTitle.textContent = title;
                
                // Show modal
                modal.show();
            });
        });
    });
</script>