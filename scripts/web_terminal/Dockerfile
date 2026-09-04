FROM tsl0922/ttyd:alpine
RUN apk add --no-cache openssh-client
EXPOSE 7681
CMD ["ttyd", "-W", "-p", "7681", "-t", "fontSize=15", "-t", "theme={\"background\": \"#1e1e2e\"}", "ssh", "-o", "StrictHostKeyChecking=no", "-o", "UserKnownHostsFile=/dev/null", "elbero@172.20.0.1"]
