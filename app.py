
# backend/app.py

from flask import Flask
from flask_cors import CORS
from config import mongo
from routes.auth import auth_bp

def create_app():
    app = Flask(__name__)
    app.config["MONGO_URI"] = "mongodb://localhost:27017/medical_assistant"
    mongo.init_app(app)
    CORS(app)

    # Enregistrer les blueprints
    app.register_blueprint(auth_bp)

    return app

if __name__ == '__main__':
    app = create_app()
    app.run(debug=True)
