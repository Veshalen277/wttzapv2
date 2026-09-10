<?php
namespace EmployeePage;

/** Optional page sections must not interrupt the work-report form. */
final class PageSections
{
    public static function render(string $label, callable $render): void
    {
        $level = ob_get_level();
        ob_start();
        try {
            $render();
            while (ob_get_level() > $level) {
                ob_end_flush();
            }
        } catch (\Throwable $error) {
            // Discard partial markup so an unfinished widget cannot swallow the form.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            self::notice($label, $error);
        }
    }

    public static function notice(string $label, \Throwable $error): void
    {
        $reference = 'work-' . bin2hex(random_bytes(6));
        error_log(sprintf('[%s] %s: %s at %s:%d', $reference,
            get_class($error), $error->getMessage(), $error->getFile(), $error->getLine()));
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<div class="alert alert-warning" role="alert">' . $safeLabel
            . ' could not be loaded. Please share this reference with your administrator: <strong>'
            . $reference . '</strong>.</div>';
    }
}
