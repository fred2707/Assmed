
const Doctor = require('../models/Doctor');

exports.getAllDoctors = async (req, res) => {
  const doctors = await Doctor.find();
  res.json(doctors);
};

exports.updateAvailability = async (req, res) => {
  const { id } = req.params;
  const { availability } = req.body;
  const doctor = await Doctor.findByIdAndUpdate(id, { availability }, { new: true });
  res.json(doctor);
};
