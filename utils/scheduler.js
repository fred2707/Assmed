
const Appointment = require('../models/Appointment');
const Doctor = require('../models/Doctor');

async function findAvailableSlots(doctorId, preferredDate) {
  const doctor = await Doctor.findById(doctorId);
  if (!doctor) return [];

  const day = new Date(preferredDate).toLocaleDateString('en-US', { weekday: 'long' });
  const availability = doctor.availability.find(a => a.day === day);
  if (!availability) return [];

  const allAppointments = await Appointment.find({ doctorId, date: preferredDate });
  const bookedTimes = allAppointments.map(app => app.time);
  
  const slots = [];
  const start = parseInt(availability.startHour.split(':')[0]);
  const end = parseInt(availability.endHour.split(':')[0]);

  for (let hour = start; hour < end; hour++) {
    const time = `${hour.toString().padStart(2, '0')}:00`;
    if (!bookedTimes.includes(time)) {
      slots.push(time);
    }
  }

  return slots;
}

module.exports = { findAvailableSlots };
