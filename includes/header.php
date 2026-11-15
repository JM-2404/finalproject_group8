
<?php
$pageTitle = $pageTitle ?? 'AccessAble | Every Space Welcomes Everyone';
$pageStyles = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Discover accessible venues and make informed decisions before visiting.">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <?php foreach ($pageStyles as $stylesheet): ?>
        <link rel="stylesheet"
              href="<?php echo htmlspecialchars($stylesheet); ?>">
    <?php endforeach; ?>

</head>
<body>
<a href="#main-content" class="skip-link">Skip to content</a>
