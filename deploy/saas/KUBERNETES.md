# SaaS Deployment — Kubernetes on VPS

This guide covers deploying Sentinel (SaaS mode) to a single VPS using k3s (lightweight Kubernetes).

---

## Prerequisites

- VPS with at least **4 GB RAM, 2 vCPU** (e.g. Hetzner CX22 ~€4/month)
- Domain with DNS A records pointing to your VPS:
  - `sentinel-app.com` → VPS IP
  - `api.sentinel-app.com` → VPS IP
- GitHub Container Registry (GHCR) access (already set up via CI)

---

## Step 1 — Install k3s on the VPS

```bash
curl -sfL https://get.k3s.io | sh -
# k3s includes Traefik Ingress + automatic Let's Encrypt TLS
```

Verify:
```bash
kubectl get nodes
```

---

## Step 2 — Create Kubernetes Secrets

Never commit secrets. Create them on the cluster directly:

```bash
kubectl create secret generic sentinel-secrets \
  --from-literal=APP_SECRET=<random-32-chars> \
  --from-literal=POSTGRES_PASSWORD=<strong-password> \
  --from-literal=MERCURE_JWT_SECRET=<random-32-chars> \
  --from-literal=MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

---

## Step 3 — Write K8s Manifests

Create `deploy/saas/k8s/` with the following files:

### `namespace.yaml`
```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: sentinel
```

### `configmap.yaml`
Environment variables that are not secret:
```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: sentinel-config
  namespace: sentinel
data:
  APP_ENV: prod
  APP_MODE: saas
  DOMAIN: sentinel-app.com
  FRONTEND_URL: https://sentinel-app.com
  MERCURE_URL: http://api/.well-known/mercure
  MERCURE_PUBLIC_URL: https://api.sentinel-app.com/.well-known/mercure
  CORS_ALLOW_ORIGIN: "^https://sentinel-app\\.com$"
```

### `api-deployment.yaml`
Contains an **init container** to run DB migrations before the app starts:
```yaml
initContainers:
  - name: migrations
    image: ghcr.io/<your-org>/sentinel-api:latest
    command: ["php", "bin/console", "doctrine:migrations:migrate", "--no-interaction"]
```

### `web-deployment.yaml`
Pass `API_URL` env var so the frontend knows where the API is:
```yaml
env:
  - name: API_URL
    value: https://api.sentinel-app.com
```

### `ingress.yaml`
Routes subdomains and handles TLS via Let's Encrypt (Traefik does this automatically with k3s):
```yaml
rules:
  - host: sentinel-app.com      → web service :80
  - host: api.sentinel-app.com  → api service :80
```

### `worker-deployment.yaml`
Same image as api, overridden command:
```yaml
command: ["php", "bin/console", "messenger:consume", "async", "failed", "--time-limit=3600"]
```

---

## Step 4 — Add Mercure Redis Transport

**Required for multi-pod deployments.** Without this, Mercure events only reach pods that received the publish request.

Add to api and worker environment:
```
MERCURE_TRANSPORT_URL=redis://redis:6379
```

This is currently **missing** from the app — add it when writing manifests.

---

## Step 5 — CI/CD: Auto-deploy on Push

Add a deploy step to `.github/workflows/build-and-push.yml` after the image push:

```yaml
- name: Deploy to k3s
  uses: appleboy/ssh-action@v1
  with:
    host: ${{ secrets.VPS_HOST }}
    username: ${{ secrets.VPS_USER }}
    key: ${{ secrets.VPS_SSH_KEY }}
    script: |
      kubectl set image deployment/sentinel-api api=ghcr.io/<org>/sentinel-api:${{ github.sha }}
      kubectl set image deployment/sentinel-web web=ghcr.io/<org>/sentinel-web:${{ github.sha }}
      kubectl rollout status deployment/sentinel-api
```

Secrets to add to GitHub: `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`.

---

## Summary of Missing Pieces (TODO)

- [ ] Write `deploy/saas/k8s/` manifests (namespace, configmap, secrets, deployments, services, ingress, worker)
- [ ] Add `MERCURE_TRANSPORT_URL=redis://...` to api/worker config
- [ ] Add migration init container to api deployment
- [ ] Add SSH deploy step to CI pipeline
- [ ] Add GitHub secrets: `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`
- [ ] Set up PostgreSQL and Redis as K8s StatefulSets (or use managed cloud services)
