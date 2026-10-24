<?php
/**
 * Layout admin bagian bawah.
 *
 * @var string[] $pageScripts Script tambahan (path relatif ke assets/)
 */
$pageScripts = isset($pageScripts) ? $pageScripts : [];
?>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">&copy; 2024 Kerja Praktik | Nur Amin</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="<?= e(asset('js/jquery-3.5.1.min.js')) ?>"></script>
    <script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('js/jquery.dataTables.min.js')) ?>"></script>
    <script src="<?= e(asset('js/dataTables.bootstrap4.min.js')) ?>"></script>
    <script src="<?= e(asset('js/scripts.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <?php foreach ($pageScripts as $script) : ?>
        <script src="<?= e(asset($script)) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
