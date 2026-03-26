import http from "http";
import fs from "fs";
import jwt from "jsonwebtoken";

const JWT_SECRET = "a9f3Kx8mQ2vL7pR1zN4tW6yB0cD5eH";

http
  .createServer((req, res) => {
    if (req.method === "GET") {
      res.writeHead(200, { "Content-Type": "text/plain" });
      res.end("Hello Apache!\n");
      return;
    }

    if (req.method === "POST") {
      if (req.url === "/login") {
        let body = "";

        req.on("data", (chunk) => {
          body += chunk;
        });

        req.on("end", () => {
          try {
            body = JSON.parse(body);

            if (!body.username || !body.password) {
              res.writeHead(400, { "Content-Type": "application/json" });
              res.end(JSON.stringify({ error: "Username and password are required" }));
              return;
            }

            // Read the users file
            const usersText = fs.readFileSync("./users.txt", "utf8");
            const users = usersText
              .trim()
              .split("\n")
              .map((line) => {
                const [username, password, userId, role] = line.split(",");
                return {
                  username: username.trim(),
                  password: password.trim(),
                  userId: Number(userId.trim()),
                  role: role.trim(),
                };
              });

            // Find matching username
            const user = users.find((u) => u.username === body.username);

            // Username not found
            if (!user) {
              res.writeHead(404, { "Content-Type": "text/plain" });
              res.end(`${body.username} not found\n`);
              return;
            }

            // Password incorrect
            if (user.password !== body.password) {
              res.writeHead(401, { "Content-Type": "text/plain" });
              res.end("Invalid password\n");
              return;
            }

            // Successful login: create JWT
            const payload = {
              userId: user.userId,
              role: user.role,
            };

            const token = jwt.sign(payload, JWT_SECRET, { expiresIn: "1h" });

            res.writeHead(200, { "Content-Type": "application/json" });
            res.end(JSON.stringify({ token }));
          } catch (err) {
            console.log(err);
            res.writeHead(500, { "Content-Type": "text/plain" });
            res.end("Server error\n");
          }
        });

        return;
      }

      res.writeHead(404, { "Content-Type": "text/plain" });
      res.end("Not found\n");
      return;
    }

    res.writeHead(404, { "Content-Type": "text/plain" });
    res.end("Not found\n");
  })
  .listen(8000);

console.log("listening on port 8000");