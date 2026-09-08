import asyncio
import signal
import sys

async def pipe(reader, writer):
    try:
        while not reader.at_eof():
            data = await reader.read(65536)
            if not data:
                break
            writer.write(data)
            await writer.drain()
    except Exception:
        pass
    finally:
        try:
            writer.close()
            await writer.wait_closed()
        except Exception:
            pass

def make_handler(target_ip, target_port):
    async def handle(client_reader, client_writer):
        try:
            remote_reader, remote_writer = await asyncio.open_connection(target_ip, target_port)
            asyncio.create_task(pipe(client_reader, remote_writer))
            asyncio.create_task(pipe(remote_reader, client_writer))
        except Exception:
            try:
                client_writer.close()
                await client_writer.wait_closed()
            except Exception:
                pass
    return handle

async def main():
    server1 = await asyncio.start_server(make_handler('192.168.2.1', 80), '0.0.0.0', 8081)
    server2 = await asyncio.start_server(make_handler('192.168.1.1', 80), '0.0.0.0', 8082)
    server3 = await asyncio.start_server(make_handler('192.168.2.194', 80), '0.0.0.0', 8083)
    print("Router ASUS Principal bridge: 0.0.0.0:8081 -> 192.168.2.1:80", flush=True)
    print("Router Vera Huawei bridge: 0.0.0.0:8082 -> 192.168.1.1:80", flush=True)
    print("Node AiMesh ASUS bridge: 0.0.0.0:8083 -> 192.168.2.194:80", flush=True)
    async with server1, server2, server3:
        await asyncio.gather(server1.serve_forever(), server2.serve_forever(), server3.serve_forever())

if __name__ == '__main__':
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        pass
