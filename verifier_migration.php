#!/usr/bin/env php
<?php
/**
 * Script de vérification de la migration des fonctionnalités
 * Usage: php verifier_migration.php
 */

echo "🔍 Vérification de la migration FarmVision\n";
echo "==========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Vérifier les services
echo "📦 Vérification des services...\n";
$services = [
    'src/Service/MeteoService.php',
    'src/Service/CarboneService.php',
    'src/Service/TelegramBotService.php',
    'src/Service/QRCodeService.php',
];

foreach ($services as $service) {
    if (file_exists($service)) {
        $success[] = "✅ $service";
    } else {
        $errors[] = "❌ $service manquant";
    }
}

// Vérifier les contrôleurs
echo "\n🎮 Vérification des contrôleurs...\n";
$controllers = [
    'src/Controller/TelegramController.php',
    'src/Controller/Admin/CarboneController.php',
    'src/Controller/Admin/VoiceChatController.php',
    'src/Controller/Admin/EquipementController.php',
    'src/Controller/Admin/MaintenanceController.php',
];

foreach ($controllers as $controller) {
    if (file_exists($controller)) {
        $success[] = "✅ $controller";
    } else {
        $errors[] = "❌ $controller manquant";
    }
}

// Vérifier les commandes
echo "\n⚡ Vérification des commandes...\n";
$commands = [
    'src/Command/TelegramGetChatIdCommand.php',
    'src/Command/TelegramPollCommand.php',
    'src/Command/TelegramSendAlertsCommand.php',
    'src/Command/TelegramSetWebhookCommand.php',
];

foreach ($commands as $command) {
    if (file_exists($command)) {
        $success[] = "✅ $command";
    } else {
        $errors[] = "❌ $command manquant";
    }
}

// Vérifier composer.json
echo "\n📋 Vérification de composer.json...\n";
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    
    $requiredPackages = [
        'irazasyed/telegram-bot-sdk',
        'cboden/ratchet',
        'symfony/mercure',
        'symfony/cache',
    ];
    
    foreach ($requiredPackages as $package) {
        if (isset($composer['require'][$package])) {
            $success[] = "✅ Package $package présent";
        } else {
            $warnings[] = "⚠️  Package $package manquant dans composer.json";
        }
    }
} else {
    $errors[] = "❌ composer.json introuvable";
}

// Vérifier .env.example
echo "\n🔧 Vérification de .env.example...\n";
if (file_exists('.env.example')) {
    $envExample = file_get_contents('.env.example');
    
    $requiredVars = [
        'TELEGRAM_BOT_TOKEN',
        'TELEGRAM_BOT_USERNAME',
        'TELEGRAM_ADMIN_CHAT_ID',
    ];
    
    foreach ($requiredVars as $var) {
        if (strpos($envExample, $var) !== false) {
            $success[] = "✅ Variable $var dans .env.example";
        } else {
            $warnings[] = "⚠️  Variable $var manquante dans .env.example";
        }
    }
} else {
    $warnings[] = "⚠️  .env.example introuvable";
}

// Vérifier .env
echo "\n🔐 Vérification de .env...\n";
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    
    if (strpos($env, 'TELEGRAM_BOT_TOKEN=') !== false) {
        $success[] = "✅ TELEGRAM_BOT_TOKEN configuré";
    } else {
        $warnings[] = "⚠️  TELEGRAM_BOT_TOKEN non configuré dans .env";
    }
    
    if (strpos($env, 'TELEGRAM_ADMIN_CHAT_ID=') !== false) {
        $success[] = "✅ TELEGRAM_ADMIN_CHAT_ID configuré";
    } else {
        $warnings[] = "⚠️  TELEGRAM_ADMIN_CHAT_ID non configuré dans .env";
    }
} else {
    $warnings[] = "⚠️  .env introuvable (créez-le à partir de .env.example)";
}

// Vérifier vendor
echo "\n📚 Vérification des dépendances...\n";
if (is_dir('vendor')) {
    $success[] = "✅ Dossier vendor présent";
    
    if (is_dir('vendor/irazasyed/telegram-bot-sdk')) {
        $success[] = "✅ telegram-bot-sdk installé";
    } else {
        $warnings[] = "⚠️  telegram-bot-sdk non installé (exécutez: composer install)";
    }
} else {
    $warnings[] = "⚠️  Dossier vendor absent (exécutez: composer install)";
}

// Vérifier la documentation
echo "\n📖 Vérification de la documentation...\n";
$docs = [
    'NOUVELLES_FONCTIONNALITES.md',
    'RESUME_MIGRATION.md',
    'INSTALLATION_NOUVELLES_FONCTIONNALITES.sh',
    'VOICE_CHAT_DOCUMENTATION.md',
    'MIGRATION_COMPLETE.md',
];

foreach ($docs as $doc) {
    if (file_exists($doc)) {
        $success[] = "✅ $doc";
    } else {
        $warnings[] = "⚠️  $doc manquant";
    }
}

// Afficher le résumé
echo "\n\n";
echo "═══════════════════════════════════════════\n";
echo "           RÉSUMÉ DE LA VÉRIFICATION        \n";
echo "═══════════════════════════════════════════\n\n";

echo "✅ Succès : " . count($success) . "\n";
echo "⚠️  Avertissements : " . count($warnings) . "\n";
echo "❌ Erreurs : " . count($errors) . "\n\n";

if (!empty($errors)) {
    echo "❌ ERREURS CRITIQUES :\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  AVERTISSEMENTS :\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
    echo "\n";
}

if (empty($errors) && empty($warnings)) {
    echo "🎉 PARFAIT ! Tous les fichiers sont en place.\n\n";
    echo "📋 Prochaines étapes :\n";
    echo "   1. Exécutez : composer install\n";
    echo "   2. Configurez .env avec vos tokens Telegram\n";
    echo "   3. Obtenez votre Chat ID : php bin/console app:telegram:get-chat-id\n";
    echo "   4. Testez le bot : php bin/console app:telegram:poll\n";
} elseif (empty($errors)) {
    echo "✅ Migration réussie avec quelques avertissements.\n";
    echo "   Consultez les avertissements ci-dessus.\n";
} else {
    echo "❌ Migration incomplète. Corrigez les erreurs ci-dessus.\n";
    exit(1);
}

echo "\n📚 Documentation complète : NOUVELLES_FONCTIONNALITES.md\n";
echo "\n";
