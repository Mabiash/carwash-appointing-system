document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Page enter animation
    const mainContent = document.querySelector('.container');
    if (mainContent) {
        mainContent.classList.add('page-enter');
        setTimeout(() => {
            mainContent.classList.add('page-enter-active');
        }, 10);
    }
    
    // Appointment booking form
    initializeBookingForm();
    
    // Service selection
    initializeServiceSelection();
    
    // Admin panel tabs
    const adminTabs = document.querySelectorAll('.admin-tab');
    if (adminTabs.length > 0) {
        adminTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-target');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Remove active class from all tabs
                adminTabs.forEach(t => {
                    t.classList.remove('active');
                });
                
                // Show selected tab content and set active class
                document.querySelector(target).style.display = 'block';
                this.classList.add('active');
            });
        });
        
        // Activate first tab by default
        adminTabs[0].click();
    }
});

function initializeBookingForm() {
    const bookingForm = document.getElementById('booking-form');
    if (!bookingForm) return;
    
    const serviceSelect = document.getElementById('service_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSlotsContainer = document.getElementById('time-slots');
    const timeInput = document.getElementById('appointment_time');
    
    alert(timeInput.value)

    // Update time slots when service or date changes
    function updateTimeSlots() {
        const serviceId = serviceSelect.value;
        const date = dateInput.value;
        
        if (!serviceId || !date) {
            timeSlotsContainer.innerHTML = '<p class="text-center text-muted">Please select a service and date first</p>';
            return;
        }
        
        timeSlotsContainer.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading available time slots...</p>';
        
        // Fetch available time slots
        fetch(`/api/get-timeslots.php?service_id=${serviceId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    timeSlotsContainer.innerHTML = '<p class="text-center text-muted">No available time slots for this date</p>';
                    return;
                }
                
                let html = '<div class="time-slots">';
                data.forEach(time => {
                    html += `<div class="time-slot" data-time="${time}">${formatTime(time)}</div>`;
                });
                html += '</div>';
                
                timeSlotsContainer.innerHTML = html;
                
                // Add click event to time slots
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', function() {
                        // Remove active class from all slots
                        document.querySelectorAll('.time-slot').forEach(s => {
                            s.classList.remove('active');
                        });
                        
                        // Add active class to selected slot
                        this.classList.add('active');
                        
                        // Set hidden input value
                        timeInput.value = this.getAttribute('data-time');
                    });
                });
            })
            .catch(error => {
                console.error('Error fetching time slots:', error);
                timeSlotsContainer.innerHTML = '<p class="text-center text-danger">Error loading time slots. Please try again.</p>';
            });
    }
    
    // Format time (HH:MM:SS to HH:MM AM/PM)
    function formatTime(timeStr) {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    // Initialize date picker
    if (dateInput) {
        // Set min date to today
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const formattedToday = `${yyyy}-${mm}-${dd}`;
        
        dateInput.setAttribute('min', formattedToday);
        
        // Add event listeners
        dateInput.addEventListener('change', updateTimeSlots);
        if (serviceSelect) {
            serviceSelect.addEventListener('change', updateTimeSlots);
        }
    }
    
    // Form validation
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            if (!timeInput.value) {
                e.preventDefault();
                alert(timeInput.value);
                return false;
            }
        });
    }
}

function initializeServiceSelection() {
    const serviceCards = document.querySelectorAll('.service-select-card');
    const serviceIdInput = document.getElementById('service_id');
    
    if (serviceCards.length > 0 && serviceIdInput) {
        serviceCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove active class from all cards
                serviceCards.forEach(c => {
                    c.classList.remove('active');
                });
                
                // Add active class to selected card
                this.classList.add('active');
                
                // Set hidden input value
                serviceIdInput.value = this.getAttribute('data-service-id');
                
                // Update visible elements
                const selectedService = document.getElementById('selected-service');
                if (selectedService) {
                    selectedService.textContent = this.getAttribute('data-service-name');
                }
                
                // If date is already selected, update time slots
                const dateInput = document.getElementById('appointment_date');
                if (dateInput && dateInput.value) {
                    const event = new Event('change');
                    dateInput.dispatchEvent(event);
                }
            });
        });
    }
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Date navigation for admin appointment view
function initializeDateNav() {
    const prevDateBtn = document.getElementById('prev-date');
    const nextDateBtn = document.getElementById('next-date');
    const dateDisplay = document.getElementById('current-date');
    const dateInput = document.getElementById('filter-date');
    
    if (prevDateBtn && nextDateBtn && dateDisplay && dateInput) {
        prevDateBtn.addEventListener('click', function() {
            const currentDate = new Date(dateInput.value);
            currentDate.setDate(currentDate.getDate() - 1);
            updateDateFilter(currentDate);
        });
        
        nextDateBtn.addEventListener('click', function() {
            const currentDate = new Date(dateInput.value);
            currentDate.setDate(currentDate.getDate() + 1);
            updateDateFilter(currentDate);
        });
        
        function updateDateFilter(date) {
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            const formattedDate = `${yyyy}-${mm}-${dd}`;
            
            dateInput.value = formattedDate;
            dateDisplay.textContent = date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Submit the form to refresh appointments
            document.getElementById('date-filter-form').submit();
        }
    }
}