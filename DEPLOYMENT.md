# 🚀 Déploiement OMPAY - Configuration Twilio

## Configuration des Variables d'Environnement Twilio

### Dans Render Dashboard

Après avoir déployé l'application sur Render, configurez ces variables d'environnement dans le dashboard :

#### Variables Twilio (Obligatoires)
```
Account_SID=AC43b91ed35991577a581210a4aa6188d6
AUTH_TOKEN=ce92e23c5c9189d7a70a08fe3a3bb819
TWILIO_NUMBER=+12188535257
```

### Étapes de Configuration

1. **Allez dans votre dashboard Render**
2. **Sélectionnez votre service OMPAY**
3. **Allez dans l'onglet "Environment"**
4. **Ajoutez ces variables une par une :**

   - `Account_SID` : Votre Account SID Twilio
   - `AUTH_TOKEN` : Votre Auth Token Twilio
   - `TWILIO_NUMBER` : Votre numéro Twilio (format: +1234567890)

5. **Redémarrez le service** pour appliquer les changements

### ⚠️ Important

- **Ne partagez jamais** vos vraies credentials Twilio
- **Utilisez des variables d'environnement** pour la sécurité
- **Testez les SMS** après configuration

### 📱 Test après Configuration

Une fois configuré, testez avec :

```bash
# Créer un compte
curl -X POST https://votre-app.onrender.com/api/auth/creercompte \
  -H "Content-Type: application/json" \
  -d '{"prenom":"Test","nom":"User","numeroTelephone":"+221779999999","email":"test@email.com","numeroCNI":"7799999991234"}'

# Vous devriez recevoir un SMS sur le numéro !
```

### 🔧 Variables Existantes

Les autres variables (DB, APP_KEY, etc.) sont déjà configurées dans `render.yaml`.

---

## 📋 Checklist Déploiement

- [ ] Image Docker poussée sur Docker Hub
- [ ] Service Render créé avec `render.yaml`
- [ ] Variables Twilio configurées dans Render
- [ ] Base de données migrée et seedée
- [ ] Test des endpoints d'authentification
- [ ] Vérification des SMS reçus