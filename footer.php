</div> <!-- End of container -->
    <!-- Bootstrap JS and dependencies -->
    <script>
  // Function to show the modal with a success or error message
  function showModal(isSuccess, message) {
    const modal = document.getElementById('messageModal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalIcon = document.getElementById('modal-icon');

    // Customize the modal based on the outcome
    if (isSuccess) {
      modalTitle.innerText = 'Success';
      modalMessage.innerText = message || 'The operation was successful.';
      modalIcon.innerHTML = `<svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
    } else {
      modalTitle.innerText = 'Error';
      modalMessage.innerText = message || 'There was an error during the operation.';
      modalIcon.innerHTML = `<svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`;
    }

    // Remove hidden class to show the modal
    modal.classList.remove('hidden');
  }

  // Close the modal when the 'Close' button is clicked
  document.getElementById('closeModal').addEventListener('click', function() {
    document.getElementById('messageModal').classList.add('hidden');
  });
</script>
<script>
  $(document).ready( function () {
    $('#myTable').DataTable();
} );
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.8/datatables.min.js"></script>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-gray-500 text-sm">
                    © <?php echo date('Y'); ?> Finance Tracker. All rights reserved.
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Privacy Policy</span>
                        Privacy
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Terms of Service</span>
                        Terms
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
