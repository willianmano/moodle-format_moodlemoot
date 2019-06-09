// Javascript functions for educsaite course format.

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="educsaite">
 *  <li class="format-educsaite-section">...</li>
 *  <li class="format-educsaite-section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'ul',
        container_class : 'topics',
        section_node : 'li',
        section_class : 'section'
    };
};

M.course.format.get_section_selector = M.course.format.get_section_selector || function() {
    var config = M.course.format.get_config();

    if (config.section_node && config.section_class) {
        return config.section_node + '.' + config.section_class;
    }

    Y.log('section_node and section_class are not defined in M.course.format.get_config', 'warn', 'moodle-course-coursebase');
    return null;
};

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT : 'course-content',
        SECTIONADDMENUS : 'section_add_menus'
    };

    var sectionlist = Y.Node.all('.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y));
    // Swap menus.
    sectionlist.item(node1).one('.' + CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.' + CSS.SECTIONADDMENUS));
};

/**
 * Process sections after ajax response
 *
 * @param {YUI} Y YUI3 instance
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 * @return void
 */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    var CSS = {
        SECTIONNAME : 'sectionname'
    };

    if (response.action === 'move') {
        // Update sections titles.
        for (var i in response.sectiontitles) {
            sectionlist.item(i).one('.' + CSS.SECTIONNAME).setContent(response.sectiontitles[i]);
        }
    }
};

M.course.format.handle_educsaite = function(e) {
    // Prevent the default button action.
    e.preventDefault();

    var confirmstring = M.util.get_string('confirmdelete', 'format_educsaite');

    // Create the confirmation dialogue.
    var confirm = new M.core.confirm({
        question: confirmstring,
        modal: true,
        visible: false
    });
    confirm.show();

    // If it is confirmed.
    confirm.on('complete-yes', function() {
        var href = e.currentTarget.getAttribute('href') + '&confirm=1';
        window.location = href;
    });
};

M.course.format.init_educsaite = function(Y) {
    Y.delegate('click', M.course.format.handle_educsaite, 'body', 'li.card .controls > a.delete');
};