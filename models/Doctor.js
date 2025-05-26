
const mongoose = require('mongoose');

const DoctorSchema = new mongoose.Schema({
  name: String,
  email: String,
  password: String,
  availability: [
    {
      day: String,
      startHour: String,
      endHour: String
    }
  ]
});

module.exports = mongoose.model('Doctor', DoctorSchema);
