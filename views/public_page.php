<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $username; ?> | HereMyLinks
    </title>
    <style>
        :root {
            --bg-color:
                <?php echo htmlspecialchars($theme['bg_color'] ?? '#F8F9FE'); ?>
            ;
            --text-primary:
                <?php echo htmlspecialchars($theme['text_color'] ?? '#1A1A1A'); ?>
            ;
            --text-secondary:
                <?php echo htmlspecialchars($theme['text_color'] ?? '#6B7280'); ?>
            ;
            --accent-color: #6366F1;
            --card-bg:
                <?php echo ($theme['bg_color'] == '#FFFFFF') ? '#F3F4F6' : '#FFFFFF'; ?>
            ;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Adjust card text color if background is dark */
        <?php
        // Simple check for dark background to adjust card text color inverted or keep accessible
        // logic can be improved. For now trusting user choice.
        ?>

        /* Dark Mode Support Logic could go here or via JS toggle */
        @media (prefers-color-scheme: dark) {
            /* :root {
                --bg-color: #111827;
                --text-primary: #F9FAFB;
                --text-secondary: #9CA3AF;
                --card-bg: #1F2937;
            } */
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 680px;
            padding: 48px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-img {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: var(--shadow);
            margin-bottom: 16px;
        }

        .username {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.025em;
        }

        .bio {
            font-size: 15px;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 32px;
            line-height: 1.5;
            max-width: 80%;
        }

        .links-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .link-card {
            background-color: var(--card-bg);
            padding: 16px 20px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .link-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .link-icon {
            width: 24px;
            height: 24px;
            margin-right: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
        }

        .link-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .link-title {
            font-weight: 600;
            flex-grow: 1;
            text-align: center;
            /* If we want icon left, text center, we need a spacer on right or just simple flow */
            /* Let's keep icon left, text left for cleaner list look, or text center if desired */
            text-align: left;
        }

        /* Spacer to balance icon if we wanted centered text */
        /* .link-spacer { width: 24px; } */

        .footer {
            margin-top: 64px;
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .footer img {
            height: 16px;
            opacity: 0.5;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Profile Selection -->
        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo $username; ?>" class="profile-img">

        <h1 class="username">@
            <?php echo $username; ?>
        </h1>

        <?php if (!empty($bio)): ?>
            <p class="bio">
                <?php echo nl2br($bio); ?>
            </p>
        <?php endif; ?>

        <!-- Links Section -->
        <div class="links-container">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer"
                    class="link-card">
                    <div class="link-icon">
                        <!-- Simple icon logic: in real app use SVG or FontAwesome library based on icon name -->
                        <!-- For now, generic link icon or specific if matched -->
                        <?php
                        $icon = $link['icon'];
                        // This part would ideally map 'instagram' to an SVG path.
                        // For simplicity, using a visual placeholder or emoji for now if text based
                        // Or assuming icon is an emoji from the app picker
                        echo '🔗';
                        ?>
                    </div>
                    <span class="link-title">
                        <?php echo htmlspecialchars($link['title']); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <span>Powered by</span>
            <strong>HereMyLinks</strong>
        </div>
    </div>

</body>

</html>