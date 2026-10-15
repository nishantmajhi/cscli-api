/* Unit 10: File Handling in C
 * Opening/closing a file, and basic input/output operations: writing
 * text to a file, then reading it back. */
#include <stdio.h>
#include <stdlib.h>

int main(void) {
    FILE *fp = fopen("data.txt", "w"); /* open for writing */
    if (fp == NULL) {
        printf("Error opening file for writing!\n");
        return 1;
    }

    fprintf(fp, "Programming in C\n");
    fprintf(fp, "First Semester, B.Sc. CSIT\n");
    fclose(fp); /* close after writing */

    fp = fopen("data.txt", "r"); /* open for reading */
    if (fp == NULL) {
        printf("Error opening file for reading!\n");
        return 1;
    }

    char line[100];
    printf("File contents:\n");
    while (fgets(line, sizeof(line), fp) != NULL) {
        printf("%s", line);
    }

    fclose(fp);
    return 0;
}
