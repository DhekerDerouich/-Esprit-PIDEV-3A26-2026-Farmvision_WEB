#!/bin/bash

echo "🚀 Installation des nouvelles fonctionnalités FarmVision"
echo "========================================================="
echo ""

# Vérifier si composer est installé
if ! command -v composer &> /dev/null
then
    echo "❌ Composer n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

echo "📦 Installation des dépendances..."
composer install

echo ""
echo "✅ Dépendances installées avec succès !"
echo ""

# Vérifier si .env existe
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env
    echo "✅ Fichier .env créé. Veuillez le configurer avec vos valeurs."
else
    echo "ℹ️  Le fichier .env existe déjà."
fi

echo ""
echo "🔧 Configuration requise dans .env :"
echo "-----------------------------------"
echo "TELEGRAM_BOT_TOKEN=votre_token_ici"
echo "TELEGRAM_BOT_USERNAME=VotreBotUsername"
echo "TELEGRAM_ADMIN_CHAT_ID=votre_chat_id_ici"
echo ""

echo "📚 Étapes suivantes :"
echo "-------------------"
echo "1. Configurez votre bot Telegram :"
echo "   - Ouvrez Telegram et cherchez @BotFather"
echo "   - Envoyez /newbot et suivez les instructions"
echo "   - Copiez le token dans .env"
echo ""
echo "2. Obtenez votre Chat ID :"
echo "   php bin/console app:telegram:get-chat-id"
echo ""
echo "3. Démarrez le bot en mode polling :"
echo "   php bin/console app:telegram:poll"
echo ""
echo "4. Testez le bot :"
echo "   php bin/console app:telegram:test"
echo ""
echo "5. Configurez les alertes automatiques (cron) :"
echo "   0 8 * * * cd $(pwd) && php bin/console app:telegram:send-alerts"
echo ""

echo "📖 Documentation complète : NOUVELLES_FONCTIONNALITES.md"
echo ""
echo "✨ Installation terminée avec succès !"
