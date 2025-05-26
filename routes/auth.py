
# backend/routes/auth.py

from flask import Blueprint, request, jsonify
from werkzeug.security import generate_password_hash
from models.patient import Patient
from config import mongo

auth_bp = Blueprint('auth', __name__)

@auth_bp.route('/api/auth/signup', methods=['POST'])
def signup():
    data = request.form
    fullname = data.get('fullname')
    email = data.get('email')
    password = data.get('password')

    if not fullname or not email or not password:
        return jsonify({'error': 'Tous les champs sont obligatoires'}), 400

    existing_patient = mongo.db.patients.find_one({'email': email})
    if existing_patient:
        return jsonify({'error': 'Cet email est déjà utilisé'}), 409

    hashed_password = generate_password_hash(password)
    patient = {
        'fullname': fullname,
        'email': email,
        'password': hashed_password
    }

    mongo.db.patients.insert_one(patient)
    return jsonify({'message': 'Compte créé avec succès'}), 201


# Ajout dans backend/routes/auth.py

from werkzeug.security import check_password_hash

@auth_bp.route('/api/auth/login', methods=['POST'])
def login():
    data = request.form
    email = data.get('email')
    password = data.get('password')

    if not email or not password:
        return jsonify({'error': 'Email et mot de passe requis'}), 400

    patient = mongo.db.patients.find_one({'email': email})
    if not patient or not check_password_hash(patient['password'], password):
        return jsonify({'error': 'Identifiants invalides'}), 401

    return jsonify({'message': 'Connexion réussie', 'fullname': patient['fullname']}), 200
