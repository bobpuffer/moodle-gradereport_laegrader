<?php
require_once $CFG->dirroot.'/lib/grade/grade_item.php';
require_once $CFG->dirroot.'/lib/grade/grade_category.php';
require_once $CFG->dirroot.'/lib/grade/grade_object.php';
require_once $CFG->dirroot.'/grade/edit/tree/lib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once($CFG->dirroot.'/grade/export/lib.php');

class grade_tree_local extends grade_tree {

    /**
     * The basic representation of the tree as a hierarchical, 3-tiered array.
     * @var object $top_element
     */
    public $top_element;

    /**
     * 2D array of grade items and categories
     * @var array $levels
     */
 //   public $levels;

    /**
     * Grade items
     * @var array $items
     */
    public $items;

    /**
     * LAE Grade items used for cycling through the get_right_rows
     * @var array $items
     */
    public $levelitems;

    /**
     * LAE structure used to get the damn category names into the category-item object
     * @var array $items
     */
    public $catitems;

    /**
     * Constructor, retrieves and stores a hierarchical array of all grade_category and grade_item
     * objects for the given courseid. Full objects are instantiated. Ordering sequence is fixed if needed.
     *
     * @param int   $courseid The Course ID
     * @param bool  $fillers include fillers and colspans, make the levels var "rectangular"
     * @param bool  $category_grade_last category grade item is the last child
     * @param array $collapsed array of collapsed categories
     * @param bool  $nooutcomes Whether or not outcomes should be included
     */
    public function grade_tree_local($courseid, $fillers=false, $category_grade_last=true,
                               $collapsed=null, $nooutcomes=false) {
        global $USER, $CFG, $COURSE, $DB;

        $this->courseid   = $courseid;
        $this->levels     = array();
        $this->context    = get_context_instance(CONTEXT_COURSE, $courseid);

        if (!empty($COURSE->id) && $COURSE->id == $this->courseid) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $this->courseid));
        }
        $this->modinfo = get_fast_modinfo($course);

        // get course grade tree
        $this->top_element = grade_category::fetch_course_tree($courseid, true);

        // no otucomes if requested
        if (!empty($nooutcomes)) {
            grade_tree_local::no_outcomes($this->top_element);
        }

        // move category item to last position in category
        if ($category_grade_last) {
            grade_tree_local::category_grade_last($this->top_element);
        }

        // key to LAE grader, no levels
        grade_tree_local::fill_levels($this->levelitems, $this->top_element, 0);

    }
    /**
     * Static recursive helper - fills the levels array, useful when accessing tree elements of one level
     *
     * @param array &$levels The levels of the grade tree through which to recurse
     * @param array &$element The seed of the recursion
     * @param int   $depth How deep are we?
     * @return void
     */
    public function fill_levels(&$levelitems, &$element, $depth) {

        // prepare unique identifier
        if ($element['type'] == 'category') {
            $element['eid'] = 'c'.$element['object']->id;
            $this->catitems[$element['object']->id] = $element['object']->fullname;
        } else if (in_array($element['type'], array('item', 'courseitem', 'categoryitem'))) {
            $element['eid'] = 'i'.$element['object']->id;
            $this->items[$element['object']->id] =& $element['object'];
            $this->levelitems[$element['object']->id] =& $element;
            if ($element['type'] == 'categoryitem' && array_key_exists($element['object']->iteminstance,$this->catitems)) {
	            $this->items[$element['object']->id]->itemname = $this->catitems[$element['object']->iteminstance];
            }
        }

        if (empty($element['children'])) {
            return;
        }
        $prev = 0;
        foreach ($element['children'] as $sortorder=>$child) {
            grade_tree_local::fill_levels($this->levelitems, $element['children'][$sortorder], $depth);
        }
    }

     /**
     * Returns name of element optionally with icon and link
     * USED BY LAEGRADER IN ORDER TO WRAP GRADE TITLES IN THE HEADER
     *
     * @param array &$element An array representing an element in the grade_tree
     * @param bool  $withlink Whether or not this header has a link
     * @param bool  $icon Whether or not to display an icon with this header
     * @param bool  $spacerifnone return spacer if no icon found
     *
     * @return string header
     */
    function get_element_header(&$element, $withlink=false, $icon=true, $spacerifnone=false) {
        $header = '';
        $titlelength = 25;

		switch ($element['type']) {
			case 'courseitem':
				$header .= 'COURSE TOTAL';
				break;
			case 'categoryitem':
				$header .= 'CATEGORY TOTAL<br />';
			default:
 		       	$header .= $element['object']->itemname;
		}
 		$header = wordwrap($header, $titlelength, '<br />');

        if ($icon) {
            $header = $this->get_element_icon($element, $spacerifnone) . '<br />' . $header;
        }

        if ($withlink) {
            $url = $this->get_activity_link($element);
            if ($url) {
                $a = new stdClass();
                $a->name = get_string('modulename', $element['object']->itemmodule);
                $title = get_string('linktoactivity', 'grades', $a);

                $header = html_writer::link($url, $header, array('title' => $title));
            }
        }

        return $header;
    }

    /*
     * LAE NEED TO INCLUDE THIS BECAUSE ITS THE ONLY WAY TO GET IT CALLED BY get_element_header ABOVE
     */
    private function get_activity_link($element) {
        global $CFG;
        /** @var array static cache of the grade.php file existence flags */
        static $hasgradephp = array();

        $itemtype = $element['object']->itemtype;
        $itemmodule = $element['object']->itemmodule;
        $iteminstance = $element['object']->iteminstance;
        $itemnumber = $element['object']->itemnumber;

        // Links only for module items that have valid instance, module and are
        // called from grade_tree with valid modinfo
        if ($itemtype != 'mod' || !$iteminstance || !$itemmodule || !$this->modinfo) {
            return null;
        }

        // Get $cm efficiently and with visibility information using modinfo
        $instances = $this->modinfo->get_instances();
        if (empty($instances[$itemmodule][$iteminstance])) {
            return null;
        }
        $cm = $instances[$itemmodule][$iteminstance];

        // Do not add link if activity is not visible to the current user
        if (!$cm->uservisible) {
            return null;
        }

        if (!array_key_exists($itemmodule, $hasgradephp)) {
            if (file_exists($CFG->dirroot . '/mod/' . $itemmodule . '/grade.php')) {
                $hasgradephp[$itemmodule] = true;
            } else {
                $hasgradephp[$itemmodule] = false;
            }
        }

        // If module has grade.php, link to that, otherwise view.php
        if ($hasgradephp[$itemmodule]) {
            $args = array('id' => $cm->id, 'itemnumber' => $itemnumber);
            if (isset($element['userid'])) {
                $args['userid'] = $element['userid'];
            }
            return new moodle_url('/mod/' . $itemmodule . '/grade.php', $args);
        } else {
            return new moodle_url('/mod/' . $itemmodule . '/view.php', array('id' => $cm->id));
        }
    }
    /**
     * Returns a specific Grade Item
     *
     * @param int $itemid The ID of the grade_item object
     *
     * @return grade_item
     * TODO: check if we really need this function, I think we do
     */
    public function get_item($itemid) {
        if (array_key_exists($itemid, $this->items)) {
            return $this->items[$itemid];
        } else {
            return false;
        }
    }

    /**
     * Parses the array in search of a given eid and returns a element object with
     * information about the element it has found.
     * @param int $id Gradetree item ID
     * @return object element
     * LAE we don't use the standard tree (somebody say, "INEFFICIENT!!") so need local function
     */
    public function locate_element($id) {
        // it is a category or item
        foreach ($this->levelitems as $key=>$element) {
            if ($key == $id) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Return hiding icon for give element
     *
     * @param array  $element An array representing an element in the grade_tree
     * @param object $gpr A grade_plugin_return object
     *
     * @return string
     */
    public function get_zerofill_icon($element, $gpr) {
        global $CFG, $OUTPUT;

        if (!has_capability('moodle/grade:manage', $this->context) and
            !has_capability('moodle/grade:hide', $this->context)) {
            return '';
        }

        $strparams = $this->get_params_for_iconstr($element);
        $strzerofill = get_string('zerofill', 'gradereport_laegrader', $strparams);

        $url = new moodle_url('/grade/report/laegrader/index.php', array('id' => $this->courseid, 'sesskey' => sesskey(), 'action' => 'quickdump'));
        $url = $gpr->add_url_params($url);

        $type = 'zerofill';
        $tooltip = $strzerofill;
        $actiontext = '<img alt="' . $type . '" class="smallicon" title="' . $strzerofill . '" src="' . $CFG->wwwroot . '/grade/report/laegrader/images/zerofill.png" />';
        $url->param('action', 'zerofill');
        $zerofillicon = $OUTPUT->action_link($url, 'text', null, array('class' => 'action-icon', 'onclick'=>'zerofill(' . $element['object']->id . ')'));
		preg_match('/(.*href=")/',$zerofillicon, $matches);
		// sending back an empty href with onclick
		$zerofillicontemp = $matches[0] . '#">' . $actiontext . '</a>';
        return $zerofillicontemp;
    }

    function limit_item($this_cat,$items,&$grade_values,&$grade_maxes) {
    	$extraused = $this_cat->is_extracredit_used();
    	if (!empty($this_cat->droplow)) {
    		asort($grade_values, SORT_NUMERIC);
    		$dropped = 0;
    		foreach ($grade_values as $itemid=>$value) {
    			if ($dropped < $this_cat->droplow) {
    				if ($extraused and $items[$itemid]->aggregationcoef > 0) {
    					// no drop low for extra credits
    				} else {
    					unset($grade_values[$itemid]);
    					unset($grade_maxes[$itemid]);
    					$dropped++;
    				}
    			} else {
    				// we have dropped enough
    				break;
    			}
    		}
    	} else if (!empty($this_cat->keephigh)) {
    		arsort($grade_values, SORT_NUMERIC);
    		$kept = 0;
    		foreach ($grade_values as $itemid=>$value) {
    			if ($extraused and $items[$itemid]->aggregationcoef > 0) {
    				// we keep all extra credits
    			} else if ($kept < $this_cat->keephigh) {
    				$kept++;
    			} else {
    				unset($grade_values[$itemid]);
    				unset($grade_maxes[$itemid]);
    			}
    		}
    	}
    }
}

/*
 * LAE keeps track of the parents of items in case we need to actually compute accurate point totals instead of everything = 100 points (same as percent, duh)
 */
function fill_parents(&$parents, &$items, $cats, $element, $idnumber,$accuratetotals = false, $alltotals = true) {
    foreach($element['children'] as $sortorder=>$child) {
        switch ($child['type']) {
            case 'courseitem':
            case 'categoryitem':
                continue 2;
            case 'category':
                $childid = $cats[$child['object']->id]->id;
                break;
            default:
                $childid = substr($child['eid'],1,8);
        }
        if (!isset($parents[$childid]) && isset($element['type']) && $element['type'] <> 'courseitem' && isset($idnumber)) {
            $parents[$childid]->id = $idnumber;
            $parents[$childid]->agg = $element['object']->aggregation;
        }
        if (! empty($child['children'])) {
            fill_parents($parents, $items, $cats, $child, $childid, $accuratetotals, $alltotals);
        }
        // accumulate max scores for parent
//        if ($accuratetotals && $alltotals) {
        if (isset($accuratetotals) && $accuratetotals && isset($alltotals) && $alltotals && ((isset($items[$childid]->aggregationcoef) && $items[$childid]->aggregationcoef <> 1) || (isset($parents[$childid]->agg) && $parents[$childid]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN))) {
            $items[$idnumber]->max_earnable += (isset($items[$childid]->max_earnable)) ? $items[$childid]->max_earnable : $items[$childid]->grademax;
        }
    }
    return;
}


function is_percentage($gradestr = null) {
    return (substr(trim($gradestr),-1,1) == '%') ? true : false;
}

/*
 * LAE need in order to get hold of the category name for the categoryitem structure without using the upper level category which we don't use
 */
Function fill_cats(&$tree) {
	foreach($tree->items as $key=>$item) {
		if (!$item->categoryid) {
			$tree->cats[$item->iteminstance] = $item;
		}
	}
}
?>