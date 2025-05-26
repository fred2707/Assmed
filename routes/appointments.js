
const express = require('express');
const { bookAppointment, getAppointmentsByDoctor } = require('../controllers/appointmentController');
const router = express.Router();

router.post('/book', bookAppointment);
router.get('/doctor/:id', getAppointmentsByDoctor);

module.exports = router;
