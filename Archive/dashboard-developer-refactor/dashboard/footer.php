</main>

<footer class="bg-dark text-light py-5">
    <div class="container-fluid border border-secondary">
        <div class="row">
            <div class="col-12 col-md-3 mb-4">
                <h5>Quick Links</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="/dashboard/users/checklist.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-calendar-check"></i> Checklist
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/users/sales_table.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-trophy"></i> Sales
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/users/league_table.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-book"></i> Performance
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/users/contact.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-envelope"></i> Support
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-md-3 mb-4">
                <h5>Legal</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="/dashboard/admin/privacy.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-bank2"></i> Privacy Policy
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/admin/terms.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-journal-album"></i> Terms of Use
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/admin/copyright.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-lightning"></i> Copyright
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/sitemap.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-map"></i> Sitemap
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-md-3 mb-4">
                <h5>Resources</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="/dashboard/messages/message_board.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-chat-dots"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/suggestions/suggestion.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-balloon-heart"></i> Suggestions
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/dashboard/users/leave_app.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-rocket-takeoff-fill"></i> Apply for Leave
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="https://wttzap.co.za/dashboard/users/policies.php" class="nav-link p-0 text-secondary">
                            <i class="bi bi-rocket"></i> Policies
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-md-3 mb-4">
                <h5>Download</h5>
                <a href="../dashboard/downloads/app-release.apk" download class="btn btn-success text-white">
                    <i class="bi bi-android2"></i> Get the App
                </a>
            </div>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-between mt-4 border-top pt-4">
            <p class="mb-0">&copy; 2024 Watches Tell Time. All rights reserved.</p>
            <ul class="list-unstyled d-flex mb-0">
                <li class="ms-3"><a class="text-light" href="#" aria-label="Twitter"><i class="bi bi-twitter" style="font-size:1.5rem;"></i></a></li>
                <li class="ms-3"><a class="text-light" href="#" aria-label="Instagram"><i class="bi bi-instagram" style="font-size:1.5rem;"></i></a></li>
                <li class="ms-3"><a class="text-light" href="#" aria-label="Facebook"><i class="bi bi-facebook" style="font-size:1.5rem;"></i></a></li>
            </ul>
        </div>
    </div>
</footer>

<!-- LOAD LIBRARIES FIRST -->

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="/dashboard/js/bootstrap.bundle.min.js"></script>

<!-- Shared UI behavior; validation belongs to each form and its server handler. -->
<script src="<?= \Portal\Html::escape(\Portal\Html::asset('js/portal.js')) ?>"></script>

</body>
</html>

<?php if (ob_get_level() > 0) { ob_end_flush(); } ?>