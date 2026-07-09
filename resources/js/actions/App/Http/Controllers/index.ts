import DashboardController from './DashboardController'
import StudentNoteController from './StudentNoteController'
import StudentNoteGroupController from './StudentNoteGroupController'
import Teams from './Teams'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    StudentNoteController: Object.assign(StudentNoteController, StudentNoteController),
    StudentNoteGroupController: Object.assign(StudentNoteGroupController, StudentNoteGroupController),
    Teams: Object.assign(Teams, Teams),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers