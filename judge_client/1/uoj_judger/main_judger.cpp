#include "uoj_judger.h"

int main(int argc, char **argv)  {
	main_judger_init(argc, argv);
	RunResult res = run_program(
			(result_path + "/run_judger_result.txt").c_str(),//run_program_result_file_name
			"/dev/null",//input_file_name
			"/dev/null",//output_file_name
            "stderr",//error_file_name
            conf_run_limit("judger", 0, RL_JUDGER_DEFAULT),//limit
            "--unsafe",
            conf_str("judger").c_str(),
            main_path.c_str(),
            work_path.c_str(),
            result_path.c_str(),
            data_path.c_str(),
            NULL);
    if (res.type != RS_AC) {
        end_judge_judgement_failed("Judgement Failed : Judger " + info_str(res));
    }
	return 0;
}
