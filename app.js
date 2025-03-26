import Fastify from 'fastify';
import cors from '@fastify/cors';
import mysql from 'mysql2/promise'; // Utilisation directe de mysql2
import dotenv from 'dotenv';

dotenv.config();

// Configuration de Fastify avec des versions stables
const fastify = Fastify({ 
  logger: true,
  ignoreTrailingSlash: true
});

// Middleware CORS sécurisé
fastify.register(cors, {
  origin: process.env.NODE_ENV === 'development' ? true : ['https://votredomaine.com'],
  methods: ['GET', 'OPTIONS'],
  allowedHeaders: ['Content-Type']
});

// Pool de connexions MySQL
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'db_cinematic',
  port: parseInt(process.env.DB_PORT) || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Vérification de la connexion DB au démarrage
fastify.addHook('onReady', async () => {
  try {
    const conn = await pool.getConnection();
    conn.release();
    fastify.log.info('✅ Connexion à MySQL établie');
  } catch (err) {
    fastify.log.error('❌ Erreur de connexion à MySQL');
    process.exit(1);
  }
});

// Routes avec gestion améliorée des erreurs
fastify.get('/api/films', async (request, reply) => {
  try {
    const [rows] = await pool.query(`
      SELECT f.*, g.nom as genre_nom 
      FROM films f
      JOIN genres g ON f.genre_id = g.id
      ORDER BY f.titre
    `);
    
    return { 
      status: 'success',
      count: rows.length,
      data: rows 
    };
  } catch (error) {
    request.log.error(error);
    return reply.status(500).send({ 
      status: 'error',
      code: 'DB_ERROR',
      message: 'Erreur de base de données' 
    });
  }
});

fastify.get('/api/films/:id', async (request, reply) => {
  try {
    const filmId = parseInt(request.params.id);
    
    if (isNaN(filmId)) {
      return reply.status(400).send({
        status: 'error',
        code: 'INVALID_ID',
        message: 'ID doit être un nombre'
      });
    }

    const [rows] = await pool.query(
      `SELECT f.*, g.nom as genre_nom 
       FROM films f
       JOIN genres g ON f.genre_id = g.id
       WHERE f.id = ?`,
      [filmId]
    );

    if (rows.length === 0) {
      return reply.status(404).send({ 
        status: 'error',
        code: 'NOT_FOUND',
        message: 'Film non trouvé' 
      });
    }

    return { status: 'success', data: rows[0] };
  } catch (error) {
    request.log.error(error);
    return reply.status(500).send({
      status: 'error',
      code: 'SERVER_ERROR',
      message: 'Erreur interne du serveur'
    });
  }
});

fastify.get('/api/films/:id/seances', async (request, reply) => {
  try {
    const filmId = parseInt(request.params.id);
    
    if (isNaN(filmId)) {
      return reply.status(400).send({
        status: 'error',
        code: 'INVALID_ID',
        message: 'ID doit être un nombre'
      });
    }

    const [rows] = await pool.query(
      `SELECT * FROM seances 
       WHERE film_id = ? 
       ORDER BY date, heure`,
      [filmId]
    );

    return { 
      status: 'success',
      count: rows.length,
      data: rows 
    };
  } catch (error) {
    request.log.error(error);
    return reply.status(500).send({
      status: 'error',
      code: 'SERVER_ERROR',
      message: 'Erreur interne du serveur'
    });
  }
});

// Gestion propre de la fermeture
const shutdown = async () => {
  fastify.log.info('🛑 Arrêt du serveur...');
  await pool.end();
  await fastify.close();
  process.exit(0);
};

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

// Démarrer le serveur
const start = async () => {
  try {
    await fastify.listen({ 
      port: parseInt(process.env.PORT) || 3000,
      host: '0.0.0.0'
    });
    fastify.log.info(`🚀 Server running on ${fastify.server.address().port}`);
  } catch (err) {
    fastify.log.error(err);
    process.exit(1);
  }
};

start();