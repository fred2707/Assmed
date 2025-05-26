
const express = require('express');
const { getAllDoctors, updateAvailability } = require('../controllers/doctorController');
const router = express.Router();

router.get('/', getAllDoctors);
router.put('/:id/availability', updateAvailability);

module.exports = router;
