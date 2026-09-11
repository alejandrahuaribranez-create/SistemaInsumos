<?php
declare(strict_types=1);
$pageEyebrow = isset($pageEyebrow) ? (string) $pageEyebrow : null;
$pageDescription = isset($pageDescription) ? (string) $pageDescription : null;
?>
<header class="page-heading">
    <div>
        <?php if ($pageEyebrow !== null): ?>
            <p class="eyebrow"><?= htmlspecialchars($pageEyebrow, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <h1><?= htmlspecialchars((string) $pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($pageDescription !== null): ?>
            <p><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php if (isset($pageActions)): ?>
        <div class="page-heading__actions"><?= $pageActions ?></div>
    <?php endif; ?>
</header>
