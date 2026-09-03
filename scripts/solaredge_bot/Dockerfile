FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production

# Copiar paquetes y módulos ya preparados
COPY package*.json ./
COPY node_modules ./node_modules
COPY memoria.json* ./
COPY index.js ./

CMD ["node", "index.js"]
