
const Appointment = require('../models/Appointment');
const { findAvailableSlots } = require('../utils/scheduler');

exports.bookAppointment = async (req, res) => {
  const { doctorId, patientId, preferredDate } = req.body;
  const slots = await findAvailableSlots(doctorId, preferredDate);
  if (slots.length === 0) return res.status(400).json({ message: 'No available slots' });

  const appointment = new Appointment({
    doctorId,
    patientId,
    date: preferredDate,
    time: slots[0] // premier créneau disponible
  });
  await appointment.save();
  res.json({ message: 'Appointment booked', appointment });
};

exports.getAppointmentsByDoctor = async (req, res) => {
  const { id } = req.params;
  const appointments = await Appointment.find({ doctorId: id }).populate('patientId');
  res.json(appointments);
};
