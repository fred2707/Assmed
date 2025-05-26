
const Patient = require('../models/Patient');
const Doctor = require('../models/Doctor');
const jwt = require('jsonwebtoken');

function generateToken(user, type) {
  return jwt.sign({ id: user._id, type }, process.env.JWT_SECRET, { expiresIn: '1d' });
}

exports.login = async (req, res) => {
  const { email, password, userType } = req.body;
  const Model = userType === 'doctor' ? Doctor : Patient;
  const user = await Model.findOne({ email, password }); // simplifié pour démo
  if (!user) return res.status(401).json({ message: 'Invalid credentials' });
  const token = generateToken(user, userType);
  res.json({ token, user });
};

exports.register = async (req, res) => {
  const { name, email, password, userType } = req.body;
  const Model = userType === 'doctor' ? Doctor : Patient;
  const user = new Model({ name, email, password });
  await user.save();
  const token = generateToken(user, userType);
  res.json({ token, user });
};
